<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /** Return bearer token and user, or null when credentials are invalid. */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail(mb_strtolower(trim($email)));

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return [
            'token' => $user->createToken('api-token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }
}
