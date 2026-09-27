<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Every CRM record belongs to one user and only that user may see or change it.
 * Other users get a 404 rather than a 403, so ids can't be probed for existence.
 */
trait OwnedByUser
{
    public function view(User $user, Model $model): Response
    {
        return $this->owns($user, $model);
    }

    public function update(User $user, Model $model): Response
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, Model $model): Response
    {
        return $this->owns($user, $model);
    }

    private function owns(User $user, Model $model): Response
    {
        // Cast: some PDO drivers hand back integer columns as strings.
        return (int) $model->getAttribute('user_id') === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
