<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'assigned_to'
    ];
    protected function casts(): array
    {
        return ['status' => RequestStatus::class];
    }
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function comments()
    {
        return $this->hasMany(RequestComment::class)->with('user')->latest();
    }
    public function activities()
    {
        return $this->hasMany(RequestActivity::class)->with('actor')->latest();
    }
}
