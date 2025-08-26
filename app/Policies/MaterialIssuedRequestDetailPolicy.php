<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MaterialIssuedRequestDetail;
use Illuminate\Auth\Access\Response;

class MaterialIssuedRequestDetailPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any MaterialIssuedRequestDetail');
    }

    public function view(User $user, MaterialIssuedRequestDetail $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View MaterialIssuedRequestDetail');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create MaterialIssuedRequestDetail');
    }

    public function update(User $user, MaterialIssuedRequestDetail $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update MaterialIssuedRequestDetail');
    }

    public function delete(User $user, MaterialIssuedRequestDetail $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete MaterialIssuedRequestDetail');
    }

    public function restore(User $user, MaterialIssuedRequestDetail $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore MaterialIssuedRequestDetail');
    }

    public function forceDelete(User $user, MaterialIssuedRequestDetail $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete MaterialIssuedRequestDetail');
    }
}