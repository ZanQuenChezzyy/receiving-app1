<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnDeliveryToVendorDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_delivery_to_vendor_id',
        'item_no',
        'quantity',
        'material_code',
        'description',
        'uoi',
    ];

    public function returnDeliveryToVendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\ReturnDeliveryToVendor::class, 'return_delivery_to_vendor_id', 'id');
    }
}
