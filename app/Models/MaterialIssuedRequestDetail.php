<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialIssuedRequestDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_issued_request_id',
        'delivery_order_receipt_detail_id',
        'description',
        'item_no',
        'stock_no',
        'location_id',
        'requested_qty',
        'issued_qty',
        'uoi',
    ];

    public static function getQtyPoAndIssued($purchaseOrderTerbitId, $itemNo, $excludeId = null): array
    {
        if (!$purchaseOrderTerbitId || !$itemNo) {
            return [0, 0, 0];
        }

        // Ambil PO terkait
        $po = \App\Models\PurchaseOrderTerbit::find($purchaseOrderTerbitId);
        if (!$po) {
            return [0, 0, 0];
        }

        // Cari baris item di PO
        $itemPo = \App\Models\PurchaseOrderTerbit::where('purchase_order_no', $po->purchase_order_no)
            ->where('item_no', $itemNo)
            ->first();

        if (!$itemPo) {
            return [0, 0, 0];
        }

        $qtyPo = (float) $itemPo->qty_po;

        // Hitung total yang sudah di-issue di MIR
        $query = self::where('item_no', $itemNo)
            ->whereHas('materialIssuedRequest', function ($q) use ($po) {
                $q->where('purchase_order_terbit_id', $po->id);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $qtyIssued = (float) $query->sum('issued_qty');

        $sisa = max(0, $qtyPo - $qtyIssued);

        return [$qtyPo, $qtyIssued, $sisa];
    }

    public function materialIssuedRequest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\MaterialIssuedRequest::class, 'material_issued_request_id', 'id');
    }

    public function location(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Location::class, 'location_id', 'id');
    }

    public function deliveryOrderReceiptDetail(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\DeliveryOrderReceiptDetail::class, 'delivery_order_receipt_detail_id', 'id');
    }

    public function deliveryOrderReceipt()
    {
        return $this->hasOneThrough(
            \App\Models\DeliveryOrderReceipt::class,
            \App\Models\DeliveryOrderReceiptDetail::class,
            'id', // foreign key di DO Receipt Detail
            'id', // foreign key di DO Receipt
            'delivery_order_receipt_detail_id', // local key di MIR Detail
            'delivery_order_receipt_id' // local key di DO Receipt Detail
        );
    }
}

