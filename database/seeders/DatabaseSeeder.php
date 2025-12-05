<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use App\Models\CleanerProfile;
use App\Models\Booking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Services
        $services = [
            [
                'title' => 'Standard Clean',
                'category' => 'Standard',
                'price' => 120.00,
                'duration' => '2h',
                'description' => 'Keep your home fresh and tidy.',
                'image' => 'https://images.unsplash.com/photo-1584622050111-993a426fbf0a?auto=format&fit=crop&w=400&q=80'
            ],
            [
                'title' => 'Deep Clean',
                'category' => 'Deep',
                'price' => 195.00,
                'duration' => '4h',
                'description' => 'Detailed scrubbing for every corner.',
                'image' => 'https://images.unsplash.com/photo-1528740561666-dc24705f0d17?auto=format&fit=crop&w=400&q=80'
            ],
            [
                'title' => 'Move-In/Out',
                'category' => 'Deep',
                'price' => 250.00,
                'duration' => '5h',
                'description' => 'Ensure your deposit return.',
                'image' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=400&q=80'
            ],
            [
                'title' => 'Eco-Friendly',
                'category' => 'Specialty',
                'price' => 140.00,
                'duration' => '3h',
                'description' => 'Safe, non-toxic plant products.',
                'image' => 'https://images.unsplash.com/photo-1628177142898-93e36e4e3a50?auto=format&fit=crop&w=400&q=80'
            ],
            [
                'title' => 'Carpet Steam',
                'category' => 'Specialty',
                'price' => 100.00,
                'duration' => '1h 30m',
                'description' => 'Revitalize your fabrics.',
                'image' => 'https://images.unsplash.com/photo-1527512860163-54854f653565?auto=format&fit=crop&w=400&q=80'
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }

        // 2. Create Users (Admin, Customer, Cleaner)
        
        // Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@leirad.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+1234567890',
        ]);

        // Customer
        $customer = User::create([
            'name' => 'Alex Johnson',
            'email' => 'alex@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'phone' => '+1 (555) 123-4567',
            'points' => 120,
            'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80'
        ]);

        // Cleaners
        $cleanerUsers = [
            [
                'name' => 'Maria Sanchez',
                'email' => 'maria@leirad.com',
                'img' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=200&q=80',
                'role' => 'Team Lead',
                'rating' => 5.0,
                'jobs' => 124,
                'skills' => ['Deep Clean', 'Sanitization', 'Windows']
            ],
            [
                'name' => 'James Hallow',
                'email' => 'james@leirad.com',
                'img' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80',
                'role' => 'Deep Expert',
                'rating' => 4.8,
                'jobs' => 89,
                'skills' => ['Carpets', 'Upholstery', 'Heavy Duty']
            ],
            [
                'name' => 'Sarah Kim',
                'email' => 'sarah@leirad.com',
                'img' => 'https://images.unsplash.com/photo-1590650516494-0c8e4a4dd67e?auto=format&fit=crop&w=200&q=80',
                'role' => 'Eco Pro',
                'rating' => 4.9,
                'jobs' => 215,
                'skills' => ['Green Clean', 'Allergen-Free', 'Pet Safe']
            ]
        ];

        foreach ($cleanerUsers as $cUser) {
            $u = User::create([
                'name' => $cUser['name'],
                'email' => $cUser['email'],
                'password' => Hash::make('password'),
                'role' => 'cleaner',
                'phone' => '+1555' . rand(100000, 999999),
            ]);

            CleanerProfile::create([
                'user_id' => $u->id,
                'job_title' => $cUser['role'],
                'img' => $cUser['img'],
                'jobs' => $cUser['jobs'],
                'rating' => $cUser['rating'],
                'skills' => $cUser['skills'],
                'experience_years' => rand(2, 10),
                'is_approved' => true
            ]);
        }

        // 3. Create Bookings
        $servicesList = Service::all();
        $cleanersList = User::where('role', 'cleaner')->get();

        // Past Bookings
        Booking::create([
            'user_id' => $customer->id,
            'service_id' => $servicesList->where('title', 'Deep Clean')->first()->id,
            'cleaner_id' => $cleanersList->where('name', 'James Hallow')->first()->id,
            'date' => now()->subDays(10)->toDateString(),
            'time' => '10:00',
            'duration' => '4h',
            'address' => '123 Maple Street, NY',
            'price' => 195.00,
            'status' => 'completed'
        ]);

        Booking::create([
            'user_id' => $customer->id,
            'service_id' => $servicesList->where('title', 'Carpet Steam')->first()->id,
            'cleaner_id' => $cleanersList->where('name', 'Sarah Kim')->first()->id,
            'date' => now()->subDays(25)->toDateString(),
            'time' => '14:00',
            'duration' => '1h 30m',
            'address' => '123 Maple Street, NY',
            'price' => 100.00,
            'status' => 'completed'
        ]);

        // Upcoming Booking
        Booking::create([
            'user_id' => $customer->id,
            'service_id' => $servicesList->where('title', 'Standard Clean')->first()->id,
            'cleaner_id' => $cleanersList->where('name', 'Maria Sanchez')->first()->id,
            'date' => now()->addDays(5)->toDateString(),
            'time' => '10:00',
            'duration' => '2h',
            'address' => '123 Maple Street, NY',
            'price' => 120.00,
            'status' => 'confirmed' // Confirmed = Upcoming
        ]);

        // Pending Booking
        Booking::create([
            'user_id' => $customer->id,
            'service_id' => $servicesList->where('title', 'Eco-Friendly')->first()->id,
            'cleaner_id' => null, // Unassigned
            'date' => now()->addDays(12)->toDateString(),
            'time' => '09:00',
            'duration' => '3h',
            'address' => '456 Oak Ave, NY',
            'price' => 140.00,
            'status' => 'pending'
        ]);
    }
}
