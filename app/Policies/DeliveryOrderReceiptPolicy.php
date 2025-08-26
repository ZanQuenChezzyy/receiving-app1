<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DeliveryOrderReceipt;
use Illuminate\Auth\Access\Response;

class DeliveryOrderReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any DeliveryOrderReceipt');
    }

    public function view(User $user, DeliveryOrderReceipt $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View DeliveryOrderReceipt');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create DeliveryOrderReceipt');
    }

    public function update(User $user, DeliveryOrderReceipt $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update DeliveryOrderReceipt');
    }

    public function delete(User $user, DeliveryOrderReceipt $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete DeliveryOrderReceipt');
    }

    public function restore(User $user, DeliveryOrderReceipt $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore DeliveryOrderReceipt');
    }

    public function forceDelete(User $user, DeliveryOrderReceipt $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete DeliveryOrderReceipt');
    }
}