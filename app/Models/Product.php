<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $SaleYear
 * @property-read string $ItemNo
 * @property-read int $remaining
 * @property-read string $UnitPrice
 */
class Product extends Model
{
    protected $table = 'dbo.vw_BS_EShop';
}
