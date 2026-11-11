<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->query()->where('email', $email)->first();

        return $user;
    }

    public function isOperationsUser(int $userId): bool
    {
        return $this->query()
            ->whereKey($userId)
            ->where('role', UserRole::Operations->value)
            ->exists();
    }
}
