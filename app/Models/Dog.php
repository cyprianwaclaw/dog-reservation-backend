<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'breed_id',
        'name',
        'age',
        'weight',
    ];

    protected $casts = [
        'age' => 'float',     // Zamiast 5.0 zwróci po prostu 5, a dla 5.5 zwróci 5.5
        'weight' => 'float',  // Zamiast 12.0 zwróci 12
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function breed()
    {
        return $this->belongsTo(Breed::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}