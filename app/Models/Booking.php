<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'cleaner_id',
        'date',
        'time',
        'duration',
        'address',
        'phone_number',
        'price',
        'status',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function cleaner()
    {
        return $this->belongsTo(User::class, 'cleaner_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
