<?php

namespace App\Policies;

use App\Models\User;
use App\Models\GoodsReceiptSlip;
use Illuminate\Auth\Access\Response;

class GoodsReceiptSlipPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any GoodsReceiptSlip');
    }

    public function view(User $user, GoodsReceiptSlip $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View GoodsReceiptSlip');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create GoodsReceiptSlip');
    }

    public function update(User $user, GoodsReceiptSlip $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update GoodsReceiptSlip');
    }

    public function delete(User $user, GoodsReceiptSlip $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete GoodsReceiptSlip');
    }

    public function restore(User $user, GoodsReceiptSlip $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore GoodsReceiptSlip');
    }

    public function forceDelete(User $user, GoodsReceiptSlip $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete GoodsReceiptSlip');
    }
}