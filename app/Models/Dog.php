<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'breed',
        'age',
        'weight',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}