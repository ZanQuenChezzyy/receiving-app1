<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderTerbit extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_item',
        'purchase_order_no',
        'item_no',
        'material_code',
        'description',
        'quantity',
        'uoi',
        'vendor_id',
        'vendor_id_name',
        'date_created',
        'delivery_date',
        'status',
        'incoterm',
    ];

    public function deliveryOrderReceipts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\DeliveryOrderReceipt::class);
    }

}