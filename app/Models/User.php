<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'manager_id',
        'department'
    ];
    protected $hidden = [
        'password',
        'remember_token'
    ];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class
        ];
    }
    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }
    public function directReports()
    {
        return $this->hasMany(self::class, 'manager_id');
    }
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'employee_id');
    }
    public function assignedRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'assigned_to');
    }
    public function isRole(UserRole $role): bool
    {
        return $this->role === $role;
    }
}
