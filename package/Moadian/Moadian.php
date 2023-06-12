<?php

namespace Package\Moadian;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Package\Moadian\Api\Api;
use Package\Moadian\Dto\Packet;
use Package\Moadian\Dto\Token;
use Package\Moadian\Services\EncryptionService;
use Package\Moadian\Services\HttpClient;
use Package\Moadian\Services\InvoiceIdService;
use Package\Moadian\Services\SignatureService;

class Moadian
{
    private Token $token;

    public function __construct(
        protected readonly string $publicKey,
        protected readonly string $privateKey,
        protected readonly string $orgKeyId,
        protected readonly string $username,
        protected readonly string $baseURL = 'https://tp.tax.gov.ir',
    )
    {
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @throws GuzzleException
     */
    public function sendInvoice(Packet $packet): \Psr\Http\Message\ResponseInterface
    {
        if (!$this->token) {
            throw new InvalidArgumentException("Set token before sending invoice!");
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->token->getToken(),
            'requestTraceId' => (string)Uuid::uuid4(),
            'timestamp' => time() * 1000,
        ];

        $httpClient = new HttpClient($this->baseURL,
            new SignatureService($this->privateKey),
            new EncryptionService($this->publicKey, $this->orgKeyId)
        );

        $path = 'req/api/self-tsp/async/normal-enqueue';

        return $httpClient->sendPackets($path, [$packet], $headers, true, true);
    }

    /**
     * @throws GuzzleException
     */
    public function getToken(): Token
    {
        $signatureService = new SignatureService($this->privateKey);

        $encryptionService = new EncryptionService($this->orgKeyId, null);

        $httpClient = new HttpClient($this->baseURL, $signatureService, $encryptionService);

        $api = new Api($this->username, $httpClient);

        return $api->getToken();
    }

    public function generateTaxId(DateTime $invoiceCreatedAt, $internalInvoiceId): string
    {
        $invoiceIdService = new InvoiceIdService($this->username);

        return $invoiceIdService->generateInvoiceId($invoiceCreatedAt, $internalInvoiceId);
    }

    /**
     * @throws GuzzleException
     */
    public function inquiryByReferenceNumber(string $referenceNumber)
    {
        $signatureService = new SignatureService($this->privateKey);
        $encryptionService = new EncryptionService($this->orgKeyId, null);
        $httpClient = new HttpClient($this->baseURL, $signatureService, $encryptionService);
        $api = new Api($this->username, $httpClient);
        $api->setToken($this->token);
        return $api->inquiryByReferenceNumber($referenceNumber);
    }

    /**
     * @throws GuzzleException
     */
    public function inquiryByUid(string $uid)
    {
        $signatureService = new SignatureService($this->privateKey);
        $encryptionService = new EncryptionService($this->orgKeyId, null);
        $httpClient = new HttpClient($this->baseURL, $signatureService, $encryptionService);
        $api = new Api($this->username, $httpClient);
        $api->setToken($this->token);
        return $api->inquiryByUid($uid, $this->username);
    }

    /**
     * @throws GuzzleException
     */
    public function inquiryByTime(int $time)
    {
        $signatureService = new SignatureService($this->privateKey);
        $encryptionService = new EncryptionService($this->orgKeyId, null);
        $httpClient = new HttpClient($this->baseURL, $signatureService, $encryptionService);
        $api = new Api($this->username, $httpClient);
        $api->setToken($this->token);
        return $api->inquiryByTime($time);
    }

    /**
     * @throws GuzzleException
     */
    public function inquiryByTimeRange(int $startDate, int $endDate)
    {
        $signatureService = new SignatureService($this->privateKey);
        $encryptionService = new EncryptionService($this->orgKeyId, null);
        $httpClient = new HttpClient($this->baseURL, $signatureService, $encryptionService);
        $api = new Api($this->username, $httpClient);
        $api->setToken($this->token);
        return $api->inquiryByTimeRange($startDate, $endDate);
    }

    /**
     * @throws GuzzleException
     */
    public function getEconomicCodeInformation(string $taxID)
    {
        $signatureService = new SignatureService($this->privateKey);
        $encryptionService = new EncryptionService($this->orgKeyId, null);
        $httpClient = new HttpClient($this->baseURL, $signatureService, $encryptionService);
        $api = new Api($this->username, $httpClient);
        $api->setToken($this->token);
        return $api->getEconomicCodeInformation($taxID);
    }

    /**
     * @throws GuzzleException
     */
    public function getFiscalInfo(): array
    {
        $signatureService = new SignatureService($this->privateKey);
        $encryptionService = new EncryptionService($this->orgKeyId, null);
        $httpClient = new HttpClient($this->baseURL, $signatureService, $encryptionService);
        $api = new Api($this->username, $httpClient);
        $api->setToken($this->token);
        return $api->getFiscalInfo();
    }
}
