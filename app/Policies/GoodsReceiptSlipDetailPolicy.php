<?php

namespace App\Policies;

use App\Models\User;
use App\Models\GoodsReceiptSlipDetail;
use Illuminate\Auth\Access\Response;

class GoodsReceiptSlipDetailPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any GoodsReceiptSlipDetail');
    }

    public function view(User $user, GoodsReceiptSlipDetail $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View GoodsReceiptSlipDetail');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create GoodsReceiptSlipDetail');
    }

    public function update(User $user, GoodsReceiptSlipDetail $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update GoodsReceiptSlipDetail');
    }

    public function delete(User $user, GoodsReceiptSlipDetail $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete GoodsReceiptSlipDetail');
    }

    public function restore(User $user, GoodsReceiptSlipDetail $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore GoodsReceiptSlipDetail');
    }

    public function forceDelete(User $user, GoodsReceiptSlipDetail $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete GoodsReceiptSlipDetail');
    }
}