<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalVpDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_vp_id',
        'code',
        'document_type',
        'goods_receipt_slip_id',
        'return_delivery_to_vendor_id',
    ];

    public function approvalVp(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\ApprovalVp::class, 'approval_vp_id', 'id');
    }

    public function goodsReceiptSlip(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\GoodsReceiptSlip::class, 'goods_receipt_slip_id', 'id');
    }

    public function returnDeliveryToVendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\ReturnDeliveryToVendor::class, 'return_delivery_to_vendor_id', 'id');
    }
}
