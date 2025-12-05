<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'booking_id',
        'cleaner_id',
        'service_id',
        'user_id',
        'rating', // Cleaner Rating
        'service_rating', // Service Rating
        'comment'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function cleaner()
    {
        return $this->belongsTo(User::class, 'cleaner_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
