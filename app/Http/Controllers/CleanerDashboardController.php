<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\CleanerProfile;
use Illuminate\Support\Facades\Auth;

class CleanerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $cleanerProfile = CleanerProfile::where('user_id', $user->id)->first();
        
        $stats = [
            'jobs_completed' => $cleanerProfile ? $cleanerProfile->jobs : 0,
            'rating' => $cleanerProfile ? $cleanerProfile->rating : 5.0,
            'earnings_today' => Booking::where('cleaner_id', $user->id)
                ->where('status', 'completed')
                ->whereDate('date', now()->toDateString())
                ->sum('price')
        ];

        $nextJob = Booking::where('cleaner_id', $user->id)
            ->where('status', 'confirmed')
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->first();
            
        if ($nextJob) {
            $nextJob->load('client', 'service');
        }

        $pendingAssignments = Booking::where('cleaner_id', $user->id)
            ->where('status', 'assigned')
            ->with(['client', 'service'])
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($job) {
                $customerImg = $job->client->avatar ?? $job->client->profile_photo_path ?? 'https://ui-avatars.com/api/?name=' . urlencode($job->client->name);
                $serviceImg = $job->service->image ?? 'https://via.placeholder.com/150';

                return [
                    'id' => $job->id,
                    'display_id' => 'BK-' . str_pad($job->id, 4, '0', STR_PAD_LEFT),
                    'date' => \Carbon\Carbon::parse($job->date . ' ' . $job->time)->format('M d, g:i A'),
                    'customer' => $job->client->name,
                    'customer_img' => $this->processImageUrl($customerImg),
                    'location' => $job->address,
                    'phone_number' => $job->phone_number ?? $job->client->phone ?? '',
                    'service' => $job->service->title,
                    'service_img' => $this->processImageUrl($serviceImg),
                    'price' => '₱' . number_format($job->price, 0),
                    'status' => 'Assigned (Action Required)',
                    'raw_status' => $job->status
                ];
            });

        $jobs = Booking::where('cleaner_id', $user->id)
            ->with(['client', 'service'])
            ->orderBy('date', 'desc')
            ->take(10)
            ->get()
            ->map(function ($job) {
                $customerImg = $job->client->avatar ?? $job->client->profile_photo_path ?? 'https://ui-avatars.com/api/?name=' . urlencode($job->client->name);
                $serviceImg = $job->service->image ?? 'https://via.placeholder.com/150';

                return [
                    'id' => $job->id, // Keep ID as int for updates
                    'display_id' => 'BK-' . str_pad($job->id, 4, '0', STR_PAD_LEFT),
                    'date' => \Carbon\Carbon::parse($job->date . ' ' . $job->time)->format('M d, g:i A'),
                    'customer' => $job->client->name,
                    'customer_img' => $this->processImageUrl($customerImg),
                    'location' => $job->address,
                    'phone_number' => $job->phone_number ?? $job->client->phone ?? '',
                    'service' => $job->service->title,
                    'service_img' => $this->processImageUrl($serviceImg),
                    'price' => '₱' . number_format($job->price, 0),
                    'status' => ucfirst($job->status),
                    'raw_status' => $job->status
                ];
            });

        // Prepare avatar URL
        $avatar = $user->profile_photo_path ?? $cleanerProfile->img ?? null;
        $avatarUrl = $this->processImageUrl($avatar);
        
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'next_job' => $nextJob ? [
                    'time' => \Carbon\Carbon::parse($nextJob->time)->format('h:i A'),
                    'customer_name' => $nextJob->client->name,
                    'address' => $nextJob->address,
                    'phone_number' => $nextJob->phone_number ?? $nextJob->client->phone ?? '',
                    'service' => $nextJob->service->title,
                    'duration' => $nextJob->duration,
                    'notes' => $nextJob->notes ?? ''
                ] : null,
                'pending_assignments' => $pendingAssignments,
                'jobs' => $jobs,
                'profile' => [
                    'name' => $user->name,
                    'title' => $cleanerProfile->job_title ?? 'Cleaner',
                    'avatar_url' => $avatarUrl,
                    'email' => $user->email,
                    'phone' => $user->phone ?? ''
                ]
            ]
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Job not found'], 404);
        }

        // Verify ownership
        if ($booking->cleaner_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled,assigned,declined'
        ]);

        $newStatus = strtolower($request->status);

        // Logic Checks
        if ($newStatus === 'confirmed') {
             // Acceptance
             if ($booking->status !== 'assigned' && $booking->status !== 'pending') {
                 return response()->json(['success' => false, 'message' => 'Can only accept assigned jobs.'], 400);
             }
        } elseif ($newStatus === 'declined') {
             // Rejection
             if ($booking->status !== 'assigned' && $booking->status !== 'pending') {
                 return response()->json(['success' => false, 'message' => 'Can only reject assigned jobs.'], 400);
             }
        } elseif ($newStatus === 'completed') {
             if ($booking->status !== 'confirmed') {
                 return response()->json(['success' => false, 'message' => 'Job must be confirmed before completing.'], 400);
             }
        }

        $booking->status = $newStatus;
        $booking->save();

        // If completed, update cleaner jobs count and maybe earnings
        if ($booking->status === 'completed') {
            $cleanerProfile = CleanerProfile::where('user_id', Auth::id())->first();
            if ($cleanerProfile) {
                $cleanerProfile->increment('jobs');
            }
        }

        return response()->json(['success' => true, 'message' => 'Status updated']);
    }

    public function schedule(Request $request)
    {
        $user = Auth::user();
        
        $jobs = Booking::where('cleaner_id', $user->id)
            ->with(['client', 'service'])
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get()
            ->map(function ($job) {
                $customerImg = $job->client->avatar ?? $job->client->profile_photo_path ?? 'https://ui-avatars.com/api/?name=' . urlencode($job->client->name);
                $serviceImg = $job->service->image ?? 'https://via.placeholder.com/150';

                return [
                    'id' => $job->id,
                    'display_id' => 'BK-' . str_pad($job->id, 4, '0', STR_PAD_LEFT),
                    'date' => \Carbon\Carbon::parse($job->date . ' ' . $job->time)->format('M d, Y g:i A'),
                    'customer' => $job->client->name,
                    'customer_img' => $this->processImageUrl($customerImg),
                    'location' => $job->address,
                    'phone_number' => $job->phone_number ?? $job->client->phone ?? '',
                    'service' => $job->service->title,
                    'service_img' => $this->processImageUrl($serviceImg),
                    'price' => '₱' . number_format($job->price, 2),
                    'status' => ucfirst($job->status),
                    'raw_status' => $job->status
                ];
            });

        return response()->json(['success' => true, 'data' => $jobs]);
    }

    private function processImageUrl($path)
    {
        if (!$path) return null;
        
        // If it's a full URL, return it
        if (str_starts_with($path, 'http')) return $path;
        
        // If it already starts with /storage/, prepend base URL
        if (str_starts_with($path, '/storage/')) return url($path);
        
        // If it starts with storage/ (no leading slash), fix it and prepend base URL
        if (str_starts_with($path, 'storage/')) return url($path);

        // If it's a relative path (e.g. uploads/..., profile-photos/...), prepend storage and base URL
        // Note: This assumes all local paths are in storage
        return url('storage/' . $path);
    }
}
