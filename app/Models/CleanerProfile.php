<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleanerProfile extends Model
{
    /** @use HasFactory<\Database\Factories\CleanerProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_title',
        'img',
        'jobs',
        'rating',
        'skills',
        'experience_years',
        'specialization',
        'valid_id_type',
        'id_document_path',
        'background_check_path',
        'is_approved',
    ];

    protected $casts = [
        'skills' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'cleaner_id', 'user_id');
    }
}
