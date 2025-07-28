<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalKembaliGrs extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal_kembali',
        'created_by',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }


    public function transmittalKembaliGrsDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TransmittalKembaliGrsDetail::class);
    }

}