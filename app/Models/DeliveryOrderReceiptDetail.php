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
        'is_qty_tolerance',
        'location_id',
    ];

    public static function getQtyPoAndReceived($purchaseOrderTerbitId, $itemNo, $excludeDetailId = null): array
    {
        $po = PurchaseOrderTerbit::find($purchaseOrderTerbitId);

        if (!$po || !$itemNo)
            return [0, 0];

        $qtyPo = (float) $po->qty_po;
        $poNo = $po->purchase_order_no;

        $query = self::where('item_no', $itemNo)
            ->whereHas('deliveryOrderReceipts', function ($q) use ($poNo) {
                $q->whereHas('purchaseOrderTerbits', fn($q2) => $q2->where('purchase_order_no', $poNo));
            });

        if ($excludeDetailId) {
            $query->where('id', '!=', $excludeDetailId);
        }

        $qtyReceived = (float) $query->sum('quantity');

        return [$qtyPo, $qtyReceived];
    }

    public function locations(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Location::class, 'location_id', 'id');
    }


    public function deliveryOrderReceipts(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\DeliveryOrderReceipt::class, 'delivery_order_receipt_id', 'id');
    }

}
