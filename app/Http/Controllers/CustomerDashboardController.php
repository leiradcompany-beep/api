<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Service;
use App\Models\CleanerProfile;
use App\Models\Booking;
use App\Models\User;

class CustomerDashboardController extends Controller
{
    private function getImageUrl($path, $placeholderSize = 200)
    {
        if (!$path) {
            return null;
        }
        // If it's a full URL, return it
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        
        // If it already starts with /storage/, return it with base url
        if (str_starts_with($path, '/storage/')) {
             return url($path);
        }
        
        // If it starts with storage/ (no leading slash), fix it
        if (str_starts_with($path, 'storage/')) {
             return url($path);
        }

        // If it's a relative path, assume storage
        return url('storage/' . $path);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Fetch Services
        $services = Service::all()->map(function ($service) {
            return [
                'id' => $service->id,
                'title' => $service->title,
                'price' => '₱' . number_format($service->price, 0), 
                'category' => $service->category,
                'rating' => $service->rating, // Return rating
                'img' => $this->getImageUrl($service->image, 400),
                'desc' => $service->description,
            ];
        });

        // 2. Fetch Top Cleaners (Limit 3)
        $cleaners = CleanerProfile::with('user')
            ->where('is_approved', true) // Ensure we only show approved cleaners
            ->orderBy('rating', 'desc')
            ->take(3)
            ->get()
            ->map(function ($profile) {
                // Count ratings for this cleaner
                $ratingCount = \App\Models\Review::where('cleaner_id', $profile->user_id)->count();

                return [
                    'id' => $profile->id,
                    'name' => $profile->user->name,
                    'role' => $profile->job_title,
                    'rating' => $profile->rating,
                    'rating_count' => $ratingCount,
                    'img' => $this->getImageUrl($profile->img, 200),
                    'skills' => $profile->skills ?? [],
                    'experience' => $profile->experience_years ?? 0,
                    'jobs_done' => $profile->jobs ?? 0,
                    'specialization' => $profile->specialization ?? 'General Cleaning'
                ];
            });

        // 3. Fetch User's Bookings (All for history, or paginated, but here we fetch enough)
        $bookings = Booking::where('user_id', $user->id)
            ->with(['service', 'cleaner', 'cleaner.cleanerProfile', 'review'])
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get()
            ->map(function ($booking) {
                $cleanerAvatar = null;
                if ($booking->cleaner) {
                     $cleanerAvatar = $booking->cleaner->avatar ?? $booking->cleaner->profile_photo_path;
                     // Check cleaner profile if user avatar is missing
                     if (!$cleanerAvatar && $booking->cleaner->cleanerProfile) {
                         $cleanerAvatar = $booking->cleaner->cleanerProfile->img;
                     }
                }

                return [
                    'id' => $booking->id,
                    'display_id' => 'BK-' . str_pad($booking->id, 4, '0', STR_PAD_LEFT),
                    'service' => $booking->service->title,
                    'service_img' => $this->getImageUrl($booking->service->image, 100),
                    'date' => \Carbon\Carbon::parse($booking->date)->format('M d, Y'),
                    'time' => \Carbon\Carbon::parse($booking->time)->format('h:i A'),
                    'cleaner' => $booking->cleaner ? $booking->cleaner->name : 'Pending',
                    'cleaner_email' => $booking->cleaner ? $booking->cleaner->email : null,
                    'cleaner_phone' => $booking->cleaner ? $booking->cleaner->phone : null,
                    'cleaner_avatar' => $this->getImageUrl($cleanerAvatar, 100),
                    'price' => '₱' . number_format($booking->price, 0),
                    'status' => ucfirst($booking->status),
                    'raw_status' => $booking->status,
                    'is_rated' => $booking->review ? true : false
                ];
            });

        // 4. Fetch Upcoming Job
        $upcomingJobModel = Booking::where('user_id', $user->id)
            ->where('status', 'confirmed') 
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->with(['service', 'cleaner', 'cleaner.cleanerProfile'])
            ->first();

        $upcomingJob = null;
        if ($upcomingJobModel) {
            $date = \Carbon\Carbon::parse($upcomingJobModel->date);
            $cleanerName = $upcomingJobModel->cleaner ? $upcomingJobModel->cleaner->name : 'Pending';
            $cleanerAvatarPath = ($upcomingJobModel->cleaner && $upcomingJobModel->cleaner->cleanerProfile) 
                ? $upcomingJobModel->cleaner->cleanerProfile->img 
                : null;
            $cleanerAvatar = $this->getImageUrl($cleanerAvatarPath, 100);

            $upcomingJob = [
                'day' => $date->format('d'),
                'month' => $date->format('M'),
                'service' => $upcomingJobModel->service->title,
                'time' => \Carbon\Carbon::parse($upcomingJobModel->time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($upcomingJobModel->time)->addHours($upcomingJobModel->duration ?? 2)->format('h:i A'),
                'address' => $upcomingJobModel->address,
                'price' => '₱' . number_format($upcomingJobModel->price, 2),
                'cleaner' => [
                    'name' => $cleanerName,
                    'avatar' => $cleanerAvatar,
                    'email' => $upcomingJobModel->cleaner ? $upcomingJobModel->cleaner->email : null,
                    'phone' => $upcomingJobModel->cleaner ? $upcomingJobModel->cleaner->phone : null
                ]
            ];
        }

        // 5. User Data
        $nameParts = explode(' ', $user->name);
        $firstName = array_shift($nameParts);
        $lastName = '';
        $middleName = '';
        
        if (count($nameParts) > 0) {
            $lastName = array_pop($nameParts);
        }
        
        if (count($nameParts) > 0) {
            $middleName = implode(' ', $nameParts);
        }

        $userData = [
            'firstName' => $firstName,
            'middleName' => $middleName,
            'lastName' => $lastName,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'address' => $user->address ?? '',
            'avatar' => $this->getImageUrl($user->avatar) ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
            'points' => $user->points ?? 0
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'services' => $services,
                'cleaners' => $cleaners,
                'bookings' => $bookings,
                'user' => $userData,
                'upcomingJob' => $upcomingJob
            ]
        ]);
    }
}
