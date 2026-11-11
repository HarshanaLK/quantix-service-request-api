<?php

namespace App\Repositories\Base;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimal shared read contract for Eloquent repositories.
 *
 * Keep approval/state mutations out of this interface: they must be
 * authorized and validated by the dedicated application services.
 */
interface EloquentRepositoryInterface
{
    public function findById(int $id, array $relations = []): ?Model;

    public function existsById(int $id): bool;
}
