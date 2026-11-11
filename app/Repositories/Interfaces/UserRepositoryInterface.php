<?php

namespace App\Repositories\Interfaces;

use App\Repositories\Base\EloquentRepositoryInterface;

use App\Models\User;

interface UserRepositoryInterface extends EloquentRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function isOperationsUser(int $userId): bool;
}
