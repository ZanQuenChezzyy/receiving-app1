<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalKirimGrs extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'code_105',
        'delivery_order_receipt_id',
        'tanggal_kirim',
        'created_by',
    ];

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id');
    }


    public function deliveryOrderReceipt(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\DeliveryOrderReceipt::class, 'delivery_order_receipt_id', 'id');
    }


    public function transmittalKembaliGrsDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TransmittalKembaliGrsDetail::class);
    }

}
