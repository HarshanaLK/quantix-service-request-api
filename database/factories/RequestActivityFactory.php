<?php

namespace Database\Factories;

use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequestActivityFactory extends Factory
{
    protected $model = RequestActivity::class;

    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'actor_id' => User::factory(),
            'action' => 'created',
            'from_status' => null,
            'to_status' => 'draft',
            'created_at' => now(),
        ];
    }
}
