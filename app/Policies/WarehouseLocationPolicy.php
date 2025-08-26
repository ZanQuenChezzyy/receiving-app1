<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WarehouseLocation;
use Illuminate\Auth\Access\Response;

class WarehouseLocationPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any WarehouseLocation');
    }

    public function view(User $user, WarehouseLocation $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View WarehouseLocation');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create WarehouseLocation');
    }

    public function update(User $user, WarehouseLocation $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update WarehouseLocation');
    }

    public function delete(User $user, WarehouseLocation $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete WarehouseLocation');
    }

    public function restore(User $user, WarehouseLocation $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore WarehouseLocation');
    }

    public function forceDelete(User $user, WarehouseLocation $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete WarehouseLocation');
    }
}