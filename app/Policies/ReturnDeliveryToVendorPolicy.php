<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ReturnDeliveryToVendor;
use Illuminate\Auth\Access\Response;

class ReturnDeliveryToVendorPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any ReturnDeliveryToVendor');
    }

    public function view(User $user, ReturnDeliveryToVendor $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View ReturnDeliveryToVendor');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create ReturnDeliveryToVendor');
    }

    public function update(User $user, ReturnDeliveryToVendor $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update ReturnDeliveryToVendor');
    }

    public function delete(User $user, ReturnDeliveryToVendor $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete ReturnDeliveryToVendor');
    }

    public function restore(User $user, ReturnDeliveryToVendor $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore ReturnDeliveryToVendor');
    }

    public function forceDelete(User $user, ReturnDeliveryToVendor $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete ReturnDeliveryToVendor');
    }
}