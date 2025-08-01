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
        if (!$purchaseOrderTerbitId || !$itemNo) {
            return [0, 0];
        }

        // Cari dulu PO-nya (dapatkan purchase_order_no-nya)
        $po = PurchaseOrderTerbit::find($purchaseOrderTerbitId);
        if (!$po) {
            return [0, 0];
        }

        // Cari baris item berdasarkan purchase_order_no + item_no
        $itemPo = PurchaseOrderTerbit::where('purchase_order_no', $po->purchase_order_no)
            ->where('item_no', $itemNo)
            ->first();

        if (!$itemPo) {
            return [0, 0];
        }

        $qtyPo = (float) $itemPo->qty_po;

        // Ambil qty yang sudah diterima untuk item tersebut
        $query = self::where('item_no', $itemNo)
            ->whereHas('deliveryOrderReceipts', function ($q) use ($po) {
                $q->whereHas('purchaseOrderTerbits', fn($q2) => $q2->where('purchase_order_no', $po->purchase_order_no));
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
    public function transmittalKembaliDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TransmittalKembaliDetail::class);
    }
}
