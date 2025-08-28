<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalKirim extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'code_103',
        'qc_destination',
        'delivery_order_receipt_id',
        'tanggal_kirim',
        'created_by',
    ];

    protected static function booted()
    {
        static::deleting(function ($transmittalKirim) {
            // Ambil semua detail yang terkait
            $detailIds = $transmittalKirim->transmittalKembaliDetails()->pluck('transmittal_kembali_id');

            // Hapus semua detail terkait
            $transmittalKirim->transmittalKembaliDetails()->delete();

            // Cek apakah transmittal_kembali terkait sudah tidak memiliki detail lagi
            foreach ($detailIds as $kembaliId) {
                $transmittalKembali = \App\Models\TransmittalKembali::find($kembaliId);

                if ($transmittalKembali && $transmittalKembali->transmittalKembaliDetails()->count() === 0) {
                    $transmittalKembali->delete();
                }
            }
        });
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id');
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
