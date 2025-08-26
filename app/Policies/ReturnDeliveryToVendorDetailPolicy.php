<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ReturnDeliveryToVendorDetail;
use Illuminate\Auth\Access\Response;

class ReturnDeliveryToVendorDetailPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any ReturnDeliveryToVendorDetail');
    }

    public function view(User $user, ReturnDeliveryToVendorDetail $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View ReturnDeliveryToVendorDetail');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create ReturnDeliveryToVendorDetail');
    }

    public function update(User $user, ReturnDeliveryToVendorDetail $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update ReturnDeliveryToVendorDetail');
    }

    public function delete(User $user, ReturnDeliveryToVendorDetail $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete ReturnDeliveryToVendorDetail');
    }

    public function restore(User $user, ReturnDeliveryToVendorDetail $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore ReturnDeliveryToVendorDetail');
    }

    public function forceDelete(User $user, ReturnDeliveryToVendorDetail $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete ReturnDeliveryToVendorDetail');
    }
}