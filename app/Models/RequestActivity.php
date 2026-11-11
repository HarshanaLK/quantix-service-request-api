<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestActivity extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'service_request_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'comment',
        'details',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'details' => 'array'
        ];
    }

    public function request()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
