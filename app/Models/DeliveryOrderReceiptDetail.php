<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrderReceiptDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_receipt_id',
        'item_no',
        'quantity',
        'material_code',
        'description',
        'uoi',
        'is_different_location',
        'location_id',
    ];

    public function locations(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Location::class, 'location_id', 'id');
    }


    public function deliveryOrderReceipts(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\DeliveryOrderReceipt::class, 'delivery_order_receipt_id', 'id');
    }

}
