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
        'do_receipt_detail_id',
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


    public function deliveryOrderReceiptDetail(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\DeliveryOrderReceiptDetail::class, 'do_receipt_detail_id', 'id');
    }

}
