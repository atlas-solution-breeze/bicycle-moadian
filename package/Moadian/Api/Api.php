<?php

namespace Package\Moadian\Api;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;
use Package\Moadian\Constants\PacketType;
use Package\Moadian\Constants\TransferConstants;
use Package\Moadian\Dto\GetTokenDto;
use Package\Moadian\Dto\InquiryByReferenceNumberDto;
use Package\Moadian\Dto\Packet;
use Package\Moadian\Dto\Token;
use Package\Moadian\Services\HttpClient;

class Api
{
    private ?Token $token = null;

    public function __construct(
        private readonly string     $username,
        private readonly HttpClient $httpClient,
    )
    {
    }

    /**
     * @throws GuzzleException
     */
    public function getToken(): Token
    {
        $path = 'req/api/self-tsp/sync/' . PacketType::GET_TOKEN;
        $packet = new Packet(
            PacketType::GET_TOKEN,
            new GetTokenDto($this->username)
        );

        $packet->setRetry(false);
        $packet->setFiscalId($this->username);

        $headers = $this->getEssentialHeaders();

        $response = $this->httpClient->sendPacket($path, $packet, $headers);

        return new Token($response['result']['data']['token'], $response['result']['data']['expiresIn']);
    }

    /**
     * @throws GuzzleException
     */
    public function inquiryByReferenceNumber(string $referenceNumber)
    {
        $path = 'req/api/self-tsp/sync/' . PacketType::INQUIRY_BY_REFERENCE_NUMBER;

        $inquiryByReferenceNumberDto = new InquiryByReferenceNumberDto();
        $inquiryByReferenceNumberDto->setReferenceNumber($referenceNumber);

        $packet = new Packet(
            PacketType::INQUIRY_BY_REFERENCE_NUMBER,
            $inquiryByReferenceNumberDto
        );

        $packet->setRetry(false);
        $packet->setFiscalId($this->username);
        $headers = $this->getEssentialHeaders();
        $headers['Authorization'] = 'Bearer ' . $this->token->getToken();

        return $this->httpClient->sendPacket($path, $packet, $headers);
    }

    /**
     * @throws GuzzleException
     */
    public function inquiryByUid(string $uid, $fiscalId)
    {
        $path = 'req/api/self-tsp/sync/' . PacketType::INQUIRY_BY_UID;

        $packet = new Packet(
            PacketType::INQUIRY_BY_UID,
            json_encode([
                [
                    'uid' => $uid,
                    'fiscalId' => $fiscalId
                ]
            ]),
        );

        $packet->setRetry(false);
        $headers = $this->getEssentialHeaders();
        $headers['Authorization'] = 'Bearer ' . $this->token->getToken();

        return $this->httpClient->sendPacket($path, $packet, $headers);
    }

    /**
     * @throws GuzzleException
     */
    public function inquiryByTime(int $time)
    {
        $path = 'req/api/self-tsp/sync/' . PacketType::INQUIRY_BY_TIME;

        $packet = new Packet(
            PacketType::INQUIRY_BY_TIME,
            json_encode([
                'time' => $time,
            ]),
        );

        $packet->setRetry(false);
        $headers = $this->getEssentialHeaders();
        $headers['Authorization'] = 'Bearer ' . $this->token->getToken();

        return $this->httpClient->sendPacket($path, $packet, $headers);
    }

    /**
     * @throws GuzzleException
     */
    public function inquiryByTimeRange(int $startDate, int $endDate)
    {
        $path = 'req/api/self-tsp/sync/' . PacketType::INQUIRY_BY_TIME_RANGE;

        $packet = new Packet(
            PacketType::INQUIRY_BY_TIME_RANGE,
            json_encode([
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]),
        );

        $packet->setRetry(false);
        $headers = $this->getEssentialHeaders();
        $headers['Authorization'] = 'Bearer ' . $this->token->getToken();

        return $this->httpClient->sendPacket($path, $packet, $headers);
    }

    /**
     * @throws GuzzleException
     */
    public function getEconomicCodeInformation(string $taxID)
    {
        $path = 'req/api/self-tsp/sync/' . PacketType::GET_ECONOMIC_CODE_INFORMATION;

        $this->requireToken();

        $packet = new Packet(
            PacketType::GET_ECONOMIC_CODE_INFORMATION,
            json_encode(["economicCode" => $taxID])
        );

        $packet->setRetry(false);
        $packet->setFiscalId($this->username);
        $headers = $this->getEssentialHeaders();
        $headers['Authorization'] = 'Bearer ' . $this->token->getToken();

        return $this->httpClient->sendPacket($path, $packet, $headers);
    }

    public function sendInvoices(array $invoiceDtos): string|null
    {
        $path = 'req/api/self-tsp/async/normal-enqueue';

        $packets = [];

        foreach ($invoiceDtos as $invoiceDto) {
            $packet = new Packet(PacketType::INVOICE_V01, $invoiceDto);
            $packet->setUid('AAA');
            $packets[] = $packet;
        }

        $headers = $this->getEssentialHeaders();

        $headers[TransferConstants::AUTHORIZATION_HEADER] = $this->token->getToken();

        try {
            $res = $this->httpClient->sendPackets($path, $packets, $headers, true, true);
            return $res->getBody()->getContents();
        } catch (Exception|GuzzleException $e) {
            return null;
        }
    }

    /**
     * @return array
     * @throws GuzzleException
     */
    public function getFiscalInfo(): array
    {
        $path = 'req/api/self-tsp/sync/' . PacketType::GET_FISCAL_INFORMATION;

        $this->requireToken();

        $packet = new Packet(PacketType::GET_FISCAL_INFORMATION, $this->username);

        $headers = $this->getEssentialHeaders();

        // $headers[TransferConstants::AUTHORIZATION_HEADER] = $this->token->getToken();
        $headers['Authorization'] = 'Bearer ' . $this->token->getToken();

        return $this->httpClient->sendPacket($path, $packet, $headers);
    }

    public function setToken(null|Token $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return array<string, string>
     */
    private function getEssentialHeaders(): array
    {
        return [
            TransferConstants::TIMESTAMP_HEADER => (string)(int)floor(microtime(true) * 1000),
            TransferConstants::REQUEST_TRACE_ID_HEADER => (string)Uuid::uuid4(),
        ];
    }

    /**
     * @throws GuzzleException
     */
    private function requireToken(): void
    {
        if ($this->token === null || $this->token->isExpired()) {
            $this->token = $this->getToken();
        }
    }
}
