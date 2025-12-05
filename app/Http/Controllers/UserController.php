<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CleanerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\Services\AuditService;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->get();

        return response()->json(['success' => true, 'data' => $users]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,customer,cleaner',
            'phone' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'email_verified_at' => now(), // Admin created users are verified by default
        ]);

        // If role is cleaner, create profile stub
        if ($user->role === 'cleaner') {
            CleanerProfile::create([
                'user_id' => $user->id,
                'job_title' => 'Cleaner',
                'is_approved' => true // Admin created, so approved
            ]);
        }
        
        AuditService::log('created', 'User', $user->id, $user->toArray());

        return response()->json(['success' => true, 'data' => $user], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with('cleanerProfile')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $user]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:admin,customer,cleaner',
            'phone' => 'nullable|string',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $oldData = $user->toArray();
        $user->update($validated);

        // Handle role change implications if needed (e.g. add/remove profile)
        // For now, assuming simple updates.

        AuditService::log('updated', 'User', $id, ['old' => $oldData, 'new' => $user->toArray()]);

        return response()->json(['success' => true, 'data' => $user]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting self (optional safety)
        if (auth()->id() == $user->id) {
            return response()->json(['success' => false, 'message' => 'Cannot delete your own account'], 403);
        }

        $oldData = $user->toArray();
        $user->delete();
        
        AuditService::log('deleted', 'User', $id, $oldData);

        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    }
}
