<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialIssuedRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal',
        'mir_no',
        'department',
        'used_for',
        'requested_by',
        'handed_over_by',
        'cost_center',
        'jor_no',
        'equipment_no',
        'reservation_no',
        'keterangan',
        'purchase_order_terbit_id',
        'created_by',
    ];

    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by', 'id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function purchaseOrderTerbit(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderTerbit::class, 'purchase_order_terbit_id', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(MaterialIssuedRequestDetail::class, 'material_issued_request_id', 'id');
    }

    public function lampirans(): HasMany
    {
        return $this->hasMany(MaterialIssuedRequestAttachment::class, 'material_issued_request_id', 'id');
    }
}
