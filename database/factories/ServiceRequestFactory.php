<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\{ServiceRequest, User};
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;
    public function definition(): array
    {
        return ['employee_id' => User::factory(), 'title' => fake()->sentence(4), 'description' => fake()->paragraph(), 'category' => 'it_support', 'priority' => 'medium', 'status' => RequestStatus::Draft];
    }
}
