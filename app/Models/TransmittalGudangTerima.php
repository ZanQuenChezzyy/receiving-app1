<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalGudangTerima extends Model
{
    use HasFactory;

    protected $fillable = [
        'transmittal_gudang_kirim_id',
        'tanggal_terima',
        'diterima_oleh',
        'catatan',
    ];

    public function transmittalGudangKirim(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\TransmittalGudangKirim::class, 'transmittal_gudang_kirim_id', 'id');
    }

    public function diterimaOleh(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'diterima_oleh', 'id');
    }

    public function transmittalGudangTerimaDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TransmittalGudangTerimaDetail::class);
    }

}