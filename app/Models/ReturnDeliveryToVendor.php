<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnDeliveryToVendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_receipt_id',
        'tanggal_terbit',
        'code',
        'code_124',
        'created_by',
        'keterangan',
    ];

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id');
    }
    public function deliveryOrderReceipts(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\DeliveryOrderReceipt::class, 'delivery_order_receipt_id', 'id');
    }
    public function returnDeliveryToVendorDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ReturnDeliveryToVendorDetail::class);
    }

    public function approvalVpDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ApprovalVpDetail::class);
    }

}