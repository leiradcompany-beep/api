<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Ensure default settings exist
        $defaults = [
            'company_name' => 'LEIRAD Home Cleaning',
            'support_email' => 'support@leirad.com',
            'address' => '123 Sparkle Ave, Clean District, NY',
            'business_hours' => json_encode([
                ['day' => 'Monday', 'start' => '09:00', 'end' => '17:00', 'active' => true, 'id' => 'mon'],
                ['day' => 'Tuesday', 'start' => '09:00', 'end' => '17:00', 'active' => true, 'id' => 'tue'],
                ['day' => 'Wednesday', 'start' => '09:00', 'end' => '17:00', 'active' => true, 'id' => 'wed'],
                ['day' => 'Thursday', 'start' => '09:00', 'end' => '17:00', 'active' => true, 'id' => 'thu'],
                ['day' => 'Friday', 'start' => '09:00', 'end' => '16:00', 'active' => true, 'id' => 'fri'],
                ['day' => 'Saturday', 'start' => '10:00', 'end' => '14:00', 'active' => false, 'id' => 'sat'],
                ['day' => 'Sunday', 'start' => 'Closed', 'end' => 'Closed', 'active' => false, 'id' => 'sun'],
            ])
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $settings = Setting::pluck('value', 'key');

        // Parse Name
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

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'firstName' => $firstName,
                    'middleName' => $middleName,
                    'lastName' => $lastName,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    // Use null to trigger default avatar in frontend if no avatar is set
                    'avatar' => $user->avatar
                ],
                'general' => [
                    'companyName' => $settings['company_name'],
                    'supportEmail' => $settings['support_email'],
                    'address' => $settings['address']
                ],
                'hours' => json_decode($settings['business_hours'])
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        // Email validation removed from here as it's disabled in frontend
        $request->validate([
            'firstName' => 'required|string',
            'middleName' => 'nullable|string',
            'lastName' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'avatar' => 'nullable|image|max:2048'
        ]);

        $name = $request->firstName;
        if ($request->middleName) {
            $name .= ' ' . $request->middleName;
        }
        if ($request->lastName) {
            $name .= ' ' . $request->lastName;
        }
        $user->name = $name;

        // $user->email = $request->email; // Do not update email
        $user->phone = $request->phone;
        $user->address = $request->address;

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists and is in storage
            if ($user->avatar && str_contains($user->avatar, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $user->avatar);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('avatar')->store('uploads/avatars', 'public');
            $user->avatar = '/storage/' . $path;

            // Sync with Cleaner Profile if exists
            if ($user->cleanerProfile) {
                 $user->cleanerProfile->update(['img' => $user->avatar]);
            }
        }

        $user->save();

        return response()->json([
            'success' => true, 
            'message' => 'Profile updated',
            'avatar' => $user->avatar
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:8'
        ]);

        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Incorrect current password'], 400);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        return response()->json(['success' => true, 'message' => 'Password updated']);
    }

    public function updateGeneral(Request $request)
    {
        $request->validate([
            'companyName' => 'required|string',
            'supportEmail' => 'required|email',
            'address' => 'required|string'
        ]);

        Setting::updateOrCreate(['key' => 'company_name'], ['value' => $request->companyName]);
        Setting::updateOrCreate(['key' => 'support_email'], ['value' => $request->supportEmail]);
        Setting::updateOrCreate(['key' => 'address'], ['value' => $request->address]);

        return response()->json(['success' => true, 'message' => 'General settings updated']);
    }

    public function updateHours(Request $request)
    {
        $request->validate([
            'hours' => 'required|array'
        ]);

        Setting::updateOrCreate(['key' => 'business_hours'], ['value' => json_encode($request->hours)]);

        return response()->json(['success' => true, 'message' => 'Business hours updated']);
    }
}
