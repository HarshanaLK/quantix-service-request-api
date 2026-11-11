<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'user_id',
        'body'
    ];
    public function request()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
