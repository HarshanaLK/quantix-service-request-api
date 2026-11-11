<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_read_methods_use_eloquent_model(): void
    {
        $user = User::factory()->create();
        $repository = app(UserRepositoryInterface::class);

        $this->assertTrue($repository->existsById($user->id));
        $this->assertSame($user->id, $repository->findById($user->id)?->id);
        $this->assertFalse($repository->existsById($user->id + 9999));
        $this->assertNull($repository->findById($user->id + 9999));
    }
}
