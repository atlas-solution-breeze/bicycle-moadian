<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $Invoice_ID
 * @property string $Reference_Number
 * @property string $Uid
 * @property string $Response
 */
class MoadianResult extends Model
{
    protected $table = 'dbo.SaleTax';

    protected $fillable = [
        'Invoice_ID',
        'Reference_Number',
        'Uid',
        'Response',
    ];
}
