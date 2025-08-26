<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalGudangKirim extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'goods_receipt_slip_id',
        'tanggal_kirim',
        'warehouse_location_id',
        'dikirim_oleh',
        'created_by',
    ];

    public function goodsReceiptSlip(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\GoodsReceiptSlip::class, 'goods_receipt_slip_id', 'id');
    }

    public function warehouseLocation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\WarehouseLocation::class, 'warehouse_location_id', 'id');
    }

    public function dikirimOleh(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'dikirim_oleh', 'id');
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id');
    }

    public function transmittalGudangKirimDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TransmittalGudangKirimDetail::class);
    }

    public function transmittalGudangTerimas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TransmittalGudangTerima::class);
    }
}
