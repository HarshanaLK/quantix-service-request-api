<?php

namespace App\Repositories\Base;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Common Eloquent query and persistence helpers.
 *
 * This is intentionally not a public generic update/delete API. Quantix
 * mutations go through policies, transactions and the workflow service.
 */
abstract class BaseRepository implements EloquentRepositoryInterface
{
    public function __construct(protected Model $model) {}

    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function findById(int $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function existsById(int $id): bool
    {
        return $this->query()->whereKey($id)->exists();
    }

    protected function requireById(int $id, array $relations = []): Model
    {
        return $this->query()->with($relations)->findOrFail($id);
    }

    protected function createModel(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    protected function saveModel(Model $record): void
    {
        $record->save();
    }

    protected function loadModel(Model $record, array $relations): void
    {
        $record->load($relations);
    }
}
