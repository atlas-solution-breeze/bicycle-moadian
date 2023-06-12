<?php

namespace App\Models;

use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Package\Moadian\Constants\PacketType;
use Package\Moadian\Dto\InvoiceBodyDto;
use Package\Moadian\Dto\InvoiceDto;
use Package\Moadian\Dto\InvoiceHeaderDto;
use Package\Moadian\Dto\InvoicePaymentDto;
use Package\Moadian\Dto\Packet;
use Package\Moadian\Moadian;

/**
 * @property-read int $DocID
 * @property-read string $company_economic_code
 * @property-read int $entity_type
 * @property-read string $customer_economic_code
 * @property-read int $amount_without_vat
 * @property-read int $vat_amount
 * @property-read int $total_amount
 * @property-read InvoiceItem $items
 * @property-read MoadianResult $moadianResult
 */
class Invoice extends Model
{
    protected $table = 'dbo.VW_BS_TaxMaster';

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'DocID');
    }

    public function moadianResult(): HasOne
    {
        return $this->hasOne(MoadianResult::class, 'Invoice_ID');
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function send(): void
    {
        $taxInfo = TaxInfo::query()->first();

        if (!env('ORG_KEY_ID')) {
            throw new Exception('Org Key ID must be set.');
        }
        if (!env('PUBLIC_KEY')) {
            throw new Exception('Public Key must be set');
        }

        $username = $taxInfo->username;
        $orgKeyId = config('app.moadian.orgKey');
        $privateKey = $taxInfo->private_key;
        $publicKey = config('app.moadian.publicKey');

        $moadian = new Moadian(
            $publicKey,
            $privateKey,
            $orgKeyId,
            $username
        );

        $taxId = $moadian->generateTaxId(new DateTime(), $this->DocID);

        $invoiceHeaderDto = (new InvoiceHeaderDto)
            ->setTaxid($taxId)
            ->setIndatim((new DateTime())->getTimestamp() * 1000)
            ->setIndati2m((new DateTime())->getTimestamp() * 1000)
            ->setInty(1)
            ->setInno(null)
            ->setInp(1)
            ->setIns(1)
            ->setTins($this->company_economic_code)
            ->setTob($this->entity_type)
            ->setBid($this->customer_economic_code)
            ->setTinb($this->customer_economic_code)
            ->setSbc(null)
            ->setBpc(null)
            ->setBbc(null)
            ->setScln(null)
            ->setScc(null)
            ->setCrn(null)
            ->setTprdis($this->amount_without_vat)
            ->setTdis(0)
            ->setTadis($this->amount_without_vat)
            ->setTvam($this->vat_amount)
            ->setTodam(0)
            ->setTbill($this->total_amount)
            ->setSetm(1)
            ->setCap($this->amount_without_vat)
            ->setInsp(null)
            ->setTvop(null)
            ->setTax17(0);

        $invoiceBodyDto = [];

        foreach ($this->items as $invoiceItem) {
            if (!$invoiceItem->tax_code or !$invoiceItem->tax_description) {
                throw new Exception('Tax code and tax description are required but not provided.');
            }
            $invoiceBodyDto[] = (new InvoiceBodyDto)
                ->setSstid($invoiceItem->tax_code)
                ->setSstt($invoiceItem->tax_description)
                ->setSstt('')
                ->setAm($invoiceItem->quantity)
                ->setMu('')
                ->setFee($invoiceItem->fee)
                ->setCfee(null)
                ->setCut(null)
                ->setExr(null)
                ->setPrdis(intval($invoiceItem->rate_without_vat))
                ->setDis(0)
                ->setAdis(intval($invoiceItem->rate_without_vat))
                ->setVra($invoiceItem->vat)
                ->setVam(intval($invoiceItem->rate_vat_amount))
                ->setOdt(null)
                ->setOdr(null)
                ->setOdam(null)
                ->setOlt(null)
                ->setOlr(null)
                ->setOlam(null)
                ->setConsfee(null)
                ->setSpro(null)
                ->setBros(null)
                ->setTcpbs(null)
                ->setCop(null)
                ->setVop(null)
                ->setBsrn(null)
                ->setTsstam($invoiceItem->rate_total_amount);
        }

        $invoicePaymentDto = (new InvoicePaymentDto)
            ->setIinn(null)
            ->setAcn(null)
            ->setTrmn(null)
            ->setTrn(null)
            ->setPcn(null)
            ->setPid(null)
            ->setPdt(null);

        $invoiceDto = (new InvoiceDto)
            ->setHeader($invoiceHeaderDto)
            ->setBody($invoiceBodyDto)
            ->setPayments([$invoicePaymentDto]);

        $packet = (new Packet(PacketType::INVOICE_V01, $invoiceDto))
            ->setFiscalId($username)
            ->setDataSignature(null)
            ->setEncryptionKeyId(null)
            ->setIv(null)
            ->setSymmetricKey(null);

        $token = $moadian->getToken();

        $moadianInvoice = $moadian
            ->setToken($token)
            ->sendInvoice($packet);

        $response = json_decode($moadianInvoice->getBody()->getContents());
        dump($response);
        $referenceNumber = $response->result[0]->referenceNumber;
        $uid = $response->result[0]->uid;

        $moadianInvoice = $moadian
            ->setToken($token)
            ->inquiryByReferenceNumber($referenceNumber);

        if ($moadianInvoice['result']['data'][0]['status'] === 'SUCCESS') {
            MoadianResult::query()->create([
                'Invoice_ID' => $this->DocID,
                'Refrence_number' => $referenceNumber,
                'Uid' => $uid,
            ]);
        }
    }
}
