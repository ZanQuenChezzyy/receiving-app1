<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MaterialIssuedRequest;
use Illuminate\Auth\Access\Response;

class MaterialIssuedRequestPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any MaterialIssuedRequest');
    }

    public function view(User $user, MaterialIssuedRequest $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View MaterialIssuedRequest');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create MaterialIssuedRequest');
    }

    public function update(User $user, MaterialIssuedRequest $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update MaterialIssuedRequest');
    }

    public function delete(User $user, MaterialIssuedRequest $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete MaterialIssuedRequest');
    }

    public function restore(User $user, MaterialIssuedRequest $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore MaterialIssuedRequest');
    }

    public function forceDelete(User $user, MaterialIssuedRequest $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete MaterialIssuedRequest');
    }
}