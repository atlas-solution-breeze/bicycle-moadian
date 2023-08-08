<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $Invoice_ID
 * @property string $reference_number
 * @property string $uid
 * @property string $status
 * @property string $response
 * @property string $date
 * @property-read Invoice $invoice
 */
class MoadianResult extends Model
{
    protected $table = 'dbo.SaleTax';

    protected $fillable = [
        'Invoice_ID',
        'reference_number',
        'uid',
        'status',
        'response',
        'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            Invoice::class,
            'Invoice_ID',
            'DocID'
        );
    }
}
