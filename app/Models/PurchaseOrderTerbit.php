<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderTerbit extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_and_item',
        'purchase_order_no',
        'item_no',
        'material_code',
        'description',
        'qty_po',
        'uoi',
        'vendor',
        'vendor_id_name',
        'date_create',
        'delivery_date_po',
        'po_status',
        'incoterm',
    ];

    public function deliveryOrderReceipts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\DeliveryOrderReceipt::class);
    }

}
