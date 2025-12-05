<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;

use App\Services\AuditService;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Fetching bookings');
        $query = Booking::with(['client', 'service', 'cleaner'])
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc');

        // Strict filtering for cleaner schedule
        if ($request->has('cleaner_id')) {
            $query->where('cleaner_id', $request->cleaner_id);
        }

        $bookings = $query->get()
            ->map(function($booking) {
                return [
                'id' => $booking->id,
                'user_id' => $booking->user_id,
                'client' => $booking->client->name,
                'client_avatar' => $booking->client->avatar,
                'service_id' => $booking->service_id,
                'service' => $booking->service->title,
                'service_image' => $booking->service->image,
                'cleaner_id' => $booking->cleaner_id,
                'cleaner' => $booking->cleaner ? $booking->cleaner->name : 'Unassigned',
                'cleaner_avatar' => $booking->cleaner ? $booking->cleaner->avatar : null,
                'date' => $booking->date,
                'time' => \Carbon\Carbon::parse($booking->time)->format('h:i A'),
                'duration' => $booking->duration,
                'address' => $booking->address,
                'phone_number' => $booking->phone_number,
                'price' => '₱' . number_format($booking->price, 2),
                'status' => $booking->status,
                // Add explicit service_name for cleaner schedule display
                'service_name' => $booking->service->title,
            ];
        });
        return response()->json(['success' => true, 'data' => $bookings]);
    }

    public function store(Request $request)
    {
        // If user is customer, force user_id to be their own ID
        if (auth()->check() && auth()->user()->role === 'customer') {
            $request->merge(['user_id' => auth()->id()]);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:services,id',
            'cleaner_id' => 'nullable|exists:users,id',
            'date' => [
                'required', 
                'date', 
                'after:today', // Next day onwards
                function ($attribute, $value, $fail) {
                    if (\Carbon\Carbon::parse($value)->isSunday()) {
                        $fail('Bookings are not available on Sundays.');
                    }
                },
            ],
            'time' => [
                'required',
                function ($attribute, $value, $fail) {
                     $time = \Carbon\Carbon::parse($value);
                     // 8:00 AM to 5:00 PM
                     // Using strict comparison: must be >= 08:00 and <= 17:00
                     // But usually last booking might need to be earlier if duration is considered.
                     // For now, we just validate start time is within range as requested.
                     $start = \Carbon\Carbon::parse('08:00');
                     $end = \Carbon\Carbon::parse('17:00');
                     
                     if ($time->lt($start) || $time->gt($end)) {
                         $fail('Booking time must be between 8:00 AM and 5:00 PM.');
                     }
                }
            ],
            'address' => 'required',
            'phone_number' => 'required|string|max:20',
        ]);

        // Calculate price/duration from service if not provided
        $data = $request->all();
        
        // Update User Profile with latest contact info
        if (auth()->check() && auth()->user()->role === 'customer') {
            $user = \App\Models\User::find(auth()->id());
            if ($user) {
                $user->update([
                    'phone' => $request->phone_number,
                    'address' => $request->address
                ]);
            }
        }

        if (!isset($data['price']) || !isset($data['duration'])) {
            $service = \App\Models\Service::find($request->service_id);
            if ($service) {
                if (!isset($data['price'])) $data['price'] = $service->price;
                if (!isset($data['duration'])) $data['duration'] = $service->duration;
            }
        }
        
        // Check for cleaner availability (Schedule Conflict Prevention)
        if ($request->has('cleaner_id') && $request->cleaner_id) {
            $startTime = \Carbon\Carbon::parse($request->date . ' ' . $request->time);
            
            // Use provided duration or service duration
            $durationStr = $request->duration;
            if (!$durationStr) {
                $service = \App\Models\Service::find($request->service_id);
                $durationStr = $service ? $service->duration : '1h';
            }
            
            // Convert duration string (e.g., "2h", "30m") to minutes
            // Simplified parser for example
            $minutes = 60; // Default
            if (preg_match('/(\d+)h/', $durationStr, $matches)) {
                $minutes = (int)$matches[1] * 60;
            } elseif (preg_match('/(\d+)m/', $durationStr, $matches)) {
                $minutes = (int)$matches[1];
            }
            
            $endTime = $startTime->copy()->addMinutes($minutes);

            // Check for overlaps
            $conflicts = Booking::where('cleaner_id', $request->cleaner_id)
                ->where('date', $request->date)
                ->where('status', '!=', 'cancelled')
                ->get()
                ->filter(function($existingBooking) use ($startTime, $endTime) {
                    // Parse existing booking times
                    $existStart = \Carbon\Carbon::parse($existingBooking->date . ' ' . $existingBooking->time);
                    
                    // Existing duration parsing
                    $existMinutes = 60;
                    if (preg_match('/(\d+)h/', $existingBooking->duration, $matches)) {
                        $existMinutes = (int)$matches[1] * 60;
                    } elseif (preg_match('/(\d+)m/', $existingBooking->duration, $matches)) {
                        $existMinutes = (int)$matches[1];
                    }
                    
                    $existEnd = $existStart->copy()->addMinutes($existMinutes);

                    // Check overlap
                    return $startTime->lt($existEnd) && $endTime->gt($existStart);
                });

            if ($conflicts->isNotEmpty()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Cleaner is not available at this time. Conflict with existing booking.'
                ], 422);
            }

            // If cleaner is assigned at creation, set status to assigned
            if (!isset($data['status'])) {
                $data['status'] = 'assigned';
            }
        }
        
        $booking = Booking::create($data);
        
        AuditService::log('created', 'Booking', $booking->id, $booking->toArray());
        
        return response()->json(['success' => true, 'data' => $booking], 201);
    }

    public function show($id)
    {
        $booking = Booking::with(['client', 'service', 'cleaner'])->find($id);
        if (!$booking) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        return response()->json(['success' => true, 'data' => $booking]);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        // Allow admin to update status even if cancelled (e.g. to revert it or fix mistake)
        // But restrict other users or if trying to change other fields of a cancelled booking
        if ($booking->status === 'cancelled' && $request->status === 'cancelled') {
             // If it's already cancelled and we aren't changing status, block edits
             return response()->json(['success' => false, 'message' => 'Cannot update a cancelled booking.'], 403);
        }

        $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'service_id' => 'sometimes|exists:services,id',
            'cleaner_id' => 'nullable|exists:users,id',
            'date' => 'sometimes|date',
            'time' => 'sometimes',
            'address' => 'sometimes',
            'status' => 'sometimes|in:pending,assigned,confirmed,completed,cancelled,declined'
        ]);

        $oldData = $booking->toArray();

        // Reassignment Logic Check
        if ($request->has('cleaner_id') && $booking->cleaner_id && $booking->cleaner_id != $request->cleaner_id) {
            if ($booking->status === 'confirmed' || $booking->status === 'completed') {
                return response()->json(['success' => false, 'message' => 'Cannot reassign a booking that has been accepted or completed.'], 403);
            }
            if ($booking->status === 'assigned') {
                return response()->json(['success' => false, 'message' => 'Booking is currently assigned and awaiting acceptance. Cannot reassign until declined or cancelled.'], 403);
            }
        }

        $data = $request->all();

        // If assigning a cleaner, set status to 'assigned' unless status is explicitly provided
        if ($request->has('cleaner_id') && $request->cleaner_id) {
            // Only change status if we are actually assigning/changing cleaner
            if ($booking->cleaner_id != $request->cleaner_id || $booking->status == 'pending' || $booking->status == 'declined') {
                 if (!isset($data['status']) || $data['status'] === 'pending') {
                     $data['status'] = 'assigned';
                 }
            }
        }

        // If status is changing to 'completed', we might want to log points or revenue here
        // For now, just update
        $booking->update($data);
        
        AuditService::log('updated', 'Booking', $id, ['old' => $oldData, 'new' => $booking->toArray()]);
        Log::info('Booking updated: ' . $booking->id);
        return response()->json(['success' => true, 'data' => $booking]);
    }

    public function cancel($id)
    {
        $booking = Booking::find($id);
        if (!$booking) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        // Check permission
        if (auth()->user()->role === 'customer' && $booking->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check status (Customer can only cancel 'pending' bookings)
        if (auth()->user()->role === 'customer' && $booking->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Cannot cancel booking that has already been processed.'], 403);
        }

        $oldData = $booking->toArray();
        $booking->status = 'cancelled';
        $booking->save();
        
        AuditService::log('cancelled', 'Booking', $id, ['old' => $oldData, 'new' => $booking->toArray()]);
        
        return response()->json(['success' => true, 'message' => 'Booking cancelled']);
    }

    public function rate(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        // 1. Authorization: Must be the booking owner
        if ($booking->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // 2. Validation: Can only rate 'completed' bookings
        if ($booking->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Can only rate completed bookings.'], 400);
        }

        // 3. Check if already rated
        if (\App\Models\Review::where('booking_id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Booking already rated.'], 400);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5', // Cleaner Rating
            'service_rating' => 'required|integer|min:1|max:5', // Service Rating
            'comment' => 'nullable|string|max:500'
        ]);

        // 4. Create Review
        $review = \App\Models\Review::create([
            'booking_id' => $id,
            'user_id' => auth()->id(),
            'cleaner_id' => $booking->cleaner_id,
            'service_id' => $booking->service_id,
            'rating' => $request->rating,
            'service_rating' => $request->service_rating,
            'comment' => $request->comment
        ]);

        // 5. Update Cleaner Profile Rating
        $cleanerProfile = \App\Models\CleanerProfile::where('user_id', $booking->cleaner_id)->first();
        if ($cleanerProfile) {
            $avgRating = \App\Models\Review::where('cleaner_id', $booking->cleaner_id)->avg('rating');
            $cleanerProfile->rating = round($avgRating, 1);
            $cleanerProfile->save();
        }
        
        // 6. Update Service Rating
        $service = \App\Models\Service::find($booking->service_id);
        if ($service) {
             $avgServiceRating = \App\Models\Review::where('service_id', $booking->service_id)->avg('service_rating');
             $service->rating = round($avgServiceRating, 1);
             $service->save();
        }

        return response()->json(['success' => true, 'message' => 'Rating submitted successfully', 'data' => $review]);
    }

    public function destroy($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            Log::warning('Booking not found for deletion: ' . $id);
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $oldData = $booking->toArray();
        $booking->delete();
        
        AuditService::log('deleted', 'Booking', $id, $oldData);
        Log::info('Booking deleted: ' . $id);
        return response()->json(['success' => true, 'message' => 'Booking deleted']);
    }

    /**
     * Create a booking from Cleaner Dashboard (Create Customer if needed)
     */
    public function storeCleanerBooking(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required',
            'address' => 'required',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date',
            'time' => 'required',
            'cleaner_id' => 'required|exists:users,id'
        ]);

        // 1. Check or Create User
        $user = \App\Models\User::where('email', $request->email)->first();
        $isNewUser = false;
        $generatedPassword = null;

        if (!$user) {
            $isNewUser = true;
            // Generate simple password: 'lastname' + '123'
            $generatedPassword = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $request->last_name)) . '123'; 
            
            $user = \App\Models\User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($generatedPassword),
                'role' => 'customer',
                'phone' => $request->phone_number,
                'address' => $request->address,
                'email_verified_at' => now()
            ]);
        }

        // 2. Calculate Price/Duration
        $service = \App\Models\Service::find($request->service_id);
        
        // 3. Create Booking
        $booking = Booking::create([
            'user_id' => $user->id,
            'cleaner_id' => $request->cleaner_id,
            'service_id' => $request->service_id,
            'date' => $request->date,
            'time' => $request->time,
            'status' => 'confirmed', // Auto-confirmed
            'price' => $service->price,
            'duration' => $service->duration,
            'address' => $request->address,
            'phone_number' => $request->phone_number
        ]);
        
        // If email failed earlier because booking was null, we can try sending it here if needed, 
        // but usually we want to send it right after creation or queue it.
        // However, since I passed $booking ?? null in the previous block, and $booking wasn't created yet,
        // the email would have null booking details. Let's fix that logic.
        
        if ($isNewUser) {
             // Resend or send here with actual booking details?
             // Actually, I should move the email sending AFTER booking creation so the email contains booking info.
             try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\CustomerAccountCreatedMail($user, $generatedPassword, $booking));
             } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send new account email: ' . $e->getMessage());
             }
        }
        
        AuditService::log('created', 'Booking', $booking->id, $booking->toArray());

        return response()->json([
            'success' => true,
            'data' => $booking,
            'user_created' => $isNewUser,
            'temp_password' => $generatedPassword
        ]);
    }
}
