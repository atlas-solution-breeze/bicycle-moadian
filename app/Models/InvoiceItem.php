<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $DocID
 * @property-read string $tax_code
 * @property-read string $tax_description
 * @property-read int $quantity
 * @property-read int $fee
 * @property-read int $rate_without_vat
 * @property-read int $vat
 * @property-read int $rate_vat_amount
 * @property-read int $rate_total_amount
 */
class InvoiceItem extends Model
{
    protected $table = 'VW_BS_TaxDetail';
}
