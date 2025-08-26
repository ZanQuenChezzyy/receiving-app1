<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PurchaseOrderTerbit;
use Illuminate\Auth\Access\Response;

class PurchaseOrderTerbitPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any PurchaseOrderTerbit');
    }

    public function view(User $user, PurchaseOrderTerbit $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View PurchaseOrderTerbit');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create PurchaseOrderTerbit');
    }

    public function update(User $user, PurchaseOrderTerbit $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update PurchaseOrderTerbit');
    }

    public function delete(User $user, PurchaseOrderTerbit $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete PurchaseOrderTerbit');
    }

    public function restore(User $user, PurchaseOrderTerbit $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore PurchaseOrderTerbit');
    }

    public function forceDelete(User $user, PurchaseOrderTerbit $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete PurchaseOrderTerbit');
    }
}