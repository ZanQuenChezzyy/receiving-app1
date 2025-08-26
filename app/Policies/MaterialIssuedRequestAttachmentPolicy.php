<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MaterialIssuedRequestAttachment;
use Illuminate\Auth\Access\Response;

class MaterialIssuedRequestAttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any MaterialIssuedRequestAttachment');
    }

    public function view(User $user, MaterialIssuedRequestAttachment $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View MaterialIssuedRequestAttachment');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create MaterialIssuedRequestAttachment');
    }

    public function update(User $user, MaterialIssuedRequestAttachment $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update MaterialIssuedRequestAttachment');
    }

    public function delete(User $user, MaterialIssuedRequestAttachment $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete MaterialIssuedRequestAttachment');
    }

    public function restore(User $user, MaterialIssuedRequestAttachment $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore MaterialIssuedRequestAttachment');
    }

    public function forceDelete(User $user, MaterialIssuedRequestAttachment $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete MaterialIssuedRequestAttachment');
    }
}