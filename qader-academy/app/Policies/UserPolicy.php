<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $user, User $model): bool
    {
        return $user->hasRole(['admin', 'super_admin']) || $user->id === $model->id;
    }

    public function update(User $user, User $model): bool
    {
        if ($user->hasRole(['super_admin'])) {
            return true;
        }

        if (!$user->hasRole(['admin'])) {
            return false;
        }

        return !$model->hasRole(['admin', 'super_admin']);
    }
}
