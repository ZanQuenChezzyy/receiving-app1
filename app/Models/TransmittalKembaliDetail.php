<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalKembaliDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transmittal_kembali_id',
        'transmittal_kirim_id',
        'delivery_order_receipt_id',
        'code',
        'code_103',
        'total_item',
    ];

    public function transmittalKembali(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\TransmittalKembali::class, 'transmittal_kembali_id', 'id');
    }
    public function transmittalKirim(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\TransmittalKirim::class, 'transmittal_kirim_id', 'id');
    }
    public function deliveryOrderReceipts(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\DeliveryOrderReceipt::class, 'delivery_order_receipt_id', 'id');
    }
}
