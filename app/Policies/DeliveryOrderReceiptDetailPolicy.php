<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DeliveryOrderReceiptDetail;
use Illuminate\Auth\Access\Response;

class DeliveryOrderReceiptDetailPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any DeliveryOrderReceiptDetail');
    }

    public function view(User $user, DeliveryOrderReceiptDetail $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View DeliveryOrderReceiptDetail');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create DeliveryOrderReceiptDetail');
    }

    public function update(User $user, DeliveryOrderReceiptDetail $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update DeliveryOrderReceiptDetail');
    }

    public function delete(User $user, DeliveryOrderReceiptDetail $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete DeliveryOrderReceiptDetail');
    }

    public function restore(User $user, DeliveryOrderReceiptDetail $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore DeliveryOrderReceiptDetail');
    }

    public function forceDelete(User $user, DeliveryOrderReceiptDetail $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete DeliveryOrderReceiptDetail');
    }
}