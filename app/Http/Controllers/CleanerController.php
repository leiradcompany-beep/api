<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CleanerProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\CleanerApprovedMail;
use App\Mail\CleanerRejectedMail;

use App\Services\AuditService;

class CleanerController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Fetching all cleaners');
        
        $query = CleanerProfile::with('user');

        if ($request->has('status')) {
            if ($request->status === 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->status === 'approved') {
                $query->where('is_approved', true);
            }
        }

        $cleaners = $query->get()->map(function ($cleaner) {
            return [
                'id' => $cleaner->id,
                'user_id' => $cleaner->user_id,
                'name' => $cleaner->user->name,
                'role' => $cleaner->job_title ?? 'Cleaner',
                'img' => $cleaner->img,
                'jobs' => $cleaner->jobs,
                'rating' => $cleaner->rating,
                'skills' => $cleaner->skills,
                'is_approved' => $cleaner->is_approved,
                'valid_id_type' => $cleaner->valid_id_type,
                'id_document_path' => $cleaner->id_document_path,
                'background_check_path' => $cleaner->background_check_path,
                'created_at' => $cleaner->created_at->toIso8601String(),
            ];
        });
        return response()->json(['success' => true, 'data' => $cleaners]);
    }

    public function publicList()
    {
        // Fetch only approved cleaners for public display
        $cleaners = CleanerProfile::with('user')
            ->where('is_approved', true)
            ->get()
            ->map(function ($cleaner) {
                return [
                    'name' => $cleaner->user->name,
                    'job_title' => $cleaner->job_title ?? 'Cleaning Specialist',
                    'img' => $cleaner->img,
                    'experience_years' => $cleaner->experience_years,
                    'skills' => $cleaner->skills,
                    'rating' => $cleaner->rating
                ];
            });
            
        return response()->json(['success' => true, 'data' => $cleaners]);
    }

    public function approve($id)
    {
        $cleaner = CleanerProfile::with('user')->find($id);
        if (!$cleaner) {
            return response()->json(['success' => false, 'message' => 'Cleaner not found'], 404);
        }

        $cleaner->is_approved = true;
        $cleaner->save();

        // Send Approval Email
        if ($cleaner->user) {
            try {
                Mail::to($cleaner->user->email)->send(new CleanerApprovedMail($cleaner->user->name));
            } catch (\Exception $e) {
                Log::error('Failed to send approval email: ' . $e->getMessage());
                // Continue execution, don't fail the request just because email failed
            }
        }

        AuditService::log('approved', 'CleanerProfile', $id);
        Log::info('Cleaner approved', ['cleaner_id' => $id, 'admin_id' => auth()->id()]);

        return response()->json(['success' => true, 'message' => 'Cleaner approved successfully']);
    }

    public function reject(Request $request, $id)
    {
        $cleaner = CleanerProfile::with('user')->find($id);
        if (!$cleaner) {
            return response()->json(['success' => false, 'message' => 'Cleaner not found'], 404);
        }

        $reason = $request->input('reason', 'Application requirements not met.');

        // Send Rejection Email before deleting
        if ($cleaner->user) {
            try {
                Mail::to($cleaner->user->email)->send(new CleanerRejectedMail($cleaner->user->name, $reason));
            } catch (\Exception $e) {
                Log::error('Failed to send rejection email: ' . $e->getMessage());
            }
        }

        $user = $cleaner->user;
        $cleanerData = $cleaner->toArray();
        $cleaner->delete();
        if($user) $user->delete();

        AuditService::log('rejected', 'CleanerProfile', $id, $cleanerData);

        return response()->json(['success' => true, 'message' => 'Cleaner application rejected']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'nullable|string',
            'skills' => 'nullable|array',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'experience_years' => 'nullable|integer'
        ]);

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role' => 'cleaner'
        ]);

        $img_path = null;
        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('cleaners', 'public');
            $img_path = '/storage/' . $path;
        }

        $cleaner = CleanerProfile::create([
            'user_id' => $user->id,
            'job_title' => $request->role,
            'skills' => $request->skills,
            'img' => $img_path,
            'experience_years' => $request->experience_years,
            'jobs' => 0,
            'rating' => 5.0
        ]);

        AuditService::log('created', 'CleanerProfile', $cleaner->id, ['user_id' => $user->id]);

        return response()->json(['success' => true, 'data' => $cleaner], 201);
    }
    
    public function show($id)
    {
         $cleaner = CleanerProfile::with('user')->find($id);
         if (!$cleaner) return response()->json(['success' => false, 'message' => 'Not found'], 404);
         
         return response()->json(['success' => true, 'data' => [
            'id' => $cleaner->id,
            'name' => $cleaner->user->name,
            'email' => $cleaner->user->email,
            'role' => $cleaner->job_title,
            'skills' => $cleaner->skills,
            'img' => $cleaner->img,
            'jobs' => $cleaner->jobs,
            'rating' => $cleaner->rating,
            'experience_years' => $cleaner->experience_years,
            'valid_id_type' => $cleaner->valid_id_type,
            'id_document_path' => $cleaner->id_document_path,
            'background_check_path' => $cleaner->background_check_path,
            'is_approved' => $cleaner->is_approved,
         ]]);
    }

    public function update(Request $request, $id)
    {
         $cleaner = CleanerProfile::with('user')->find($id);
         if (!$cleaner) return response()->json(['success' => false, 'message' => 'Not found'], 404);

         $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $cleaner->user_id,
            'role' => 'nullable|string',
            'skills' => 'nullable|array',
            'img' => 'nullable',
            'experience_years' => 'nullable|integer'
         ]);

         $oldData = $cleaner->toArray();

         if ($request->has('role')) $cleaner->job_title = $request->role;
         if ($request->has('skills')) $cleaner->skills = $request->skills;
         if ($request->has('is_approved')) $cleaner->is_approved = $request->is_approved;
         
         if ($request->hasFile('img')) {
            $request->validate([
                'img' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            $path = $request->file('img')->store('cleaners', 'public');
            $cleaner->img = '/storage/' . $path;
         } elseif ($request->has('img')) {
             $cleaner->img = $request->img;
         }

         if ($request->has('experience_years')) $cleaner->experience_years = $request->experience_years;
         $cleaner->save();
         
         if ($request->has('name')) $cleaner->user->name = $request->name;
         if ($request->has('email')) $cleaner->user->email = $request->email;
         $cleaner->user->save();

        AuditService::log('updated', 'CleanerProfile', $id, ['old' => $oldData, 'new' => $cleaner->toArray()]);
        Log::info('Cleaner updated: ' . $id);
        return response()->json(['success' => true, 'data' => $cleaner]);
    }

    public function destroy($id)
    {
        $cleaner = CleanerProfile::find($id);
        if ($cleaner) {
            $cleanerData = $cleaner->toArray();
            $cleaner->user->delete(); // Delete user, cascade deletes profile
            AuditService::log('deleted', 'CleanerProfile', $id, $cleanerData);
            Log::info('Cleaner deleted: ' . $id);
        } else {
            Log::warning('Cleaner not found for deletion: ' . $id);
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        return response()->json(['success' => true, 'message' => 'Cleaner deleted']);
    }
}
