<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'price',
        'rating',
        'duration',
        'description',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rating' => 'decimal:2',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
