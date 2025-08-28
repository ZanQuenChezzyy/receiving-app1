<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DeliveryOrderReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_terbit_id',
        'nomor_do',
        'location_id',
        'received_date',
        'received_by',
        'created_by',
        'tahapan',
        'do_code',
        'post_103',
    ];

    protected static function booted()
    {
        static::saved(function ($do) {
            // Pastikan relasi PO dimuat
            if (!$do->relationLoaded('purchaseOrderTerbits')) {
                $do->load('purchaseOrderTerbits');
            }

            $nomorPo = $do->purchaseOrderTerbits?->purchase_order_no ?? '';
            $nomorDo = preg_replace('/[^A-Za-z0-9]/', '', $do->nomor_do ?? '');
            $tanggal = $do->received_date ? Carbon::parse($do->received_date)->format('dmY') : '';

            $generatedCode = $nomorPo . $nomorDo . $tanggal;

            // Hanya update jika berbeda
            if ($do->do_code !== $generatedCode) {
                $do->do_code = $generatedCode;
                $do->saveQuietly(); // Hindari trigger loop event
            }
        });
    }

    public function receivedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by', 'id');
    }
    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id');
    }
    public function locations(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Location::class, 'location_id', 'id');
    }
    public function purchaseOrderTerbits(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\PurchaseOrderTerbit::class, 'purchase_order_terbit_id', 'id');
    }
    public function deliveryOrderReceiptDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\DeliveryOrderReceiptDetail::class);
    }
    public function transmittalKirims(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TransmittalKirim::class);
    }
    public function goodsReceiptSlips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\GoodsReceiptSlip::class);
    }
    public function returnDeliveryToVendors(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ReturnDeliveryToVendor::class);
    }
    public function transmittalKembaliDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TransmittalKembaliDetail::class);
    }
}
