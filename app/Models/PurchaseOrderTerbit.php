<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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


    public function materialIssuedRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\MaterialIssuedRequest::class);
    }

    public function goodsReceiptSlipDetails(): HasManyThrough
    {
        return $this->hasManyThrough(
            GoodsReceiptSlipDetail::class,
            GoodsReceiptSlip::class,
            'delivery_order_receipt_id', // FK di goods_receipt_slip
            'goods_receipt_slip_id',     // FK di goods_receipt_slip_detail
            'id',                        // PK di purchase_order_terbit
            'id'                         // PK di goods_receipt_slip
        );
    }

    public function returnDeliveryToVendorDetails(): HasManyThrough
    {
        return $this->hasManyThrough(
            ReturnDeliveryToVendorDetail::class,
            ReturnDeliveryToVendor::class,
            'delivery_order_receipt_id', // FK di return_delivery_to_vendor
            'return_delivery_to_vendor_id', // FK di return_delivery_to_vendor_detail
            'id',
            'id'
        );
    }
}
