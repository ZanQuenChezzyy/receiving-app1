<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptSlipDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_slip_id',
        'delivery_order_receipt_detail_id',
        'item_no',
        'quantity',
        'material_code',
        'description',
        'uoi',
    ];

    public function goodsReceiptSlip(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\GoodsReceiptSlip::class, 'goods_receipt_slip_id', 'id');
    }

    public function deliveryOrderReceiptDetail(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\DeliveryOrderReceiptDetail::class, 'delivery_order_receipt_detail_id', 'id');
    }
}
