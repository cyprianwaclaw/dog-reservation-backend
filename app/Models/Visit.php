<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'dog_id',
        'service_id',
        'start_time',
        'end_time',
        'price',
        'description',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function dog()
    {
        return $this->belongsTo(Dog::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}