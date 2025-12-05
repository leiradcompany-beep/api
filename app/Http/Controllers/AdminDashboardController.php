<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use App\Models\Service;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // 1. Calculate Stats
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        // Bookings Today & Yesterday (Trend)
        // Request: "Bookings Today to be displayed are all status of booking of customer"
        // Request Update: "Bookings Today are having based on the date today and the created_at of booking table"
        $bookingsToday = Booking::whereDate('created_at', $today)->count(); // Includes all statuses, based on creation date
        $bookingsYesterday = Booking::whereDate('created_at', $yesterday)->count();
        $bookingsTrend = $this->calculateTrend($bookingsToday, $bookingsYesterday);
        
        // Total Revenue & Revenue Today (Trend: +$X today)
        // Request: "Total Revenue are having based on the completed status of the booking"
        $totalRevenue = Booking::where('status', 'completed')->sum('price');
        $revenueToday = Booking::where('status', 'completed')->whereDate('date', $today)->sum('price');
        $revenueTrend = [
            'direction' => 'up',
            'text' => '+₱' . number_format($revenueToday, 2) . ' today'
        ];
        
        // Active Clients & New Clients This Week (Trend)
        $activeClients = User::where('role', 'customer')->whereHas('bookings')->count();
        $newClientsThisWeek = User::where('role', 'customer')->where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $clientsTrend = [
            'direction' => 'up',
            'text' => '+' . $newClientsThisWeek . ' new this week'
        ];
            
        // Availability Rate (Percentage of approved cleaners vs total registered cleaners)
        $totalCleaners = User::where('role', 'cleaner')->count();
        $approvedCleaners = \App\Models\CleanerProfile::where('is_approved', true)->count();
        $availabilityRate = $totalCleaners > 0 ? round(($approvedCleaners / $totalCleaners) * 100) : 0;
        // Simple trend for availability (e.g. just showing total count context)
        $availabilityTrend = [
            'direction' => 'neutral',
            'text' => $approvedCleaners . ' / ' . $totalCleaners . ' approved'
        ];

        // 2. Fetch Recent Bookings (Limit 5)
        $recentBookings = Booking::with(['client', 'service', 'cleaner'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'client_name' => $booking->client ? $booking->client->name : 'Unknown Client',
                    'client_avatar' => $booking->client ? $booking->client->avatar : null,
                    'service_name' => $booking->service ? $booking->service->title : 'Unknown Service',
                    'service_image' => $booking->service ? $booking->service->image : null,
                    'cleaner_name' => $booking->cleaner ? $booking->cleaner->name : 'Unassigned',
                    'cleaner_avatar' => $booking->cleaner ? $booking->cleaner->avatar : null,
                    'date_time' => Carbon::parse($booking->date . ' ' . $booking->time)->format('M d, Y h:i A'),
                    'status' => ucfirst($booking->status),
                    'price' => number_format($booking->price, 2)
                ];
            });

        // 3. Fetch Notifications (New Cleaner Requests)
        $notifications = \App\Models\CleanerProfile::with('user')
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'type' => 'info', // cleaner_request
                    'title' => 'New Cleaner Request',
                    'message' => $profile->user ? $profile->user->name . ' requested approval.' : 'Unknown User requested approval.',
                    'time_ago' => $profile->created_at->diffForHumans(),
                    'read' => false // Assuming all pending are unread for now
                ];
            });

        return response()->json([
            'success' => true,
            'stats' => [
                'bookings_today' => $bookingsToday,
                'bookings_trend' => $bookingsTrend,
                'total_revenue' => $totalRevenue,
                'revenue_trend' => $revenueTrend,
                'active_clients' => $activeClients,
                'clients_trend' => $clientsTrend,
                'availability_rate' => $availabilityRate,
                'availability_trend' => $availabilityTrend
            ],
            'recent_bookings' => $recentBookings,
            'notifications' => $notifications
        ]);
    }

    public function analytics()
    {
        // 1. KPI: Total Revenue
        $totalRevenue = Booking::where('status', 'completed')->sum('price');
        $lastMonthRevenue = Booking::where('status', 'completed')
            ->whereMonth('date', Carbon::now()->subMonth()->month)
            ->sum('price');
        
        // Calculate revenue trend
        $revenueTrend = 0;
        if ($lastMonthRevenue > 0) {
            $currentMonthRevenue = Booking::where('status', 'completed')
                ->whereMonth('date', Carbon::now()->month)
                ->sum('price');
            $revenueTrend = round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100);
        }

        // 2. KPI: Jobs Completed
        $totalJobs = Booking::where('status', 'completed')->count();
        $lastMonthJobs = Booking::where('status', 'completed')
            ->whereMonth('date', Carbon::now()->subMonth()->month)
            ->count();
        $currentMonthJobs = Booking::where('status', 'completed')
            ->whereMonth('date', Carbon::now()->month)
            ->count();
            
        $jobsTrend = 0;
        if ($lastMonthJobs > 0) {
            $jobsTrend = round((($currentMonthJobs - $lastMonthJobs) / $lastMonthJobs) * 100);
        }

        // 3. KPI: Avg Order Value
        $avgOrder = $totalJobs > 0 ? $totalRevenue / $totalJobs : 0;
        $lastMonthAvg = $lastMonthJobs > 0 ? $lastMonthRevenue / $lastMonthJobs : 0;
        
        $avgOrderTrend = 0;
        if ($lastMonthAvg > 0) {
            $avgOrderTrend = round((($avgOrder - $lastMonthAvg) / $lastMonthAvg) * 100);
        }

        // 4. KPI: Cleaner Availability
        $totalCleaners = User::where('role', 'cleaner')->count();
        $approvedCleaners = \App\Models\CleanerProfile::where('is_approved', true)->count();
        $availability = $totalCleaners > 0 ? round(($approvedCleaners / $totalCleaners) * 100) : 0;
        $availabilityTrend = 0; // Simplified for now

        // 5. Chart: Revenue Trends (Last 6 Months)
        $revenueLabels = [];
        $revenueData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');
            $revenue = Booking::where('status', 'completed')
                ->whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->sum('price');
            
            $revenueLabels[] = $monthName;
            $revenueData[] = $revenue;
        }

        // 6. Chart: Service Distribution
        $services = Service::withCount(['bookings' => function($query) {
            $query->where('status', 'completed');
        }])->get();
        
        $categoryLabels = $services->pluck('title')->toArray();
        $categoryData = $services->pluck('bookings_count')->toArray();

        // 7. Chart: Top Cleaners
        $topCleaners = User::where('role', 'cleaner')
            ->withCount(['cleanerBookings' => function($query) {
                $query->where('status', 'completed');
            }])
            ->orderByDesc('cleaner_bookings_count')
            ->take(5)
            ->get();
            
        $cleanerLabels = $topCleaners->pluck('name')->toArray();
        $cleanerData = $topCleaners->pluck('cleaner_bookings_count')->toArray();

        // 8. Chart: Client Retention (New vs Returning)
        // Logic: Returning client has > 1 booking
        $returningClients = User::where('role', 'customer')
            ->has('bookings', '>', 1)
            ->count();
        $newClients = User::where('role', 'customer')
            ->has('bookings', '=', 1)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'kpi' => [
                    'revenue' => $totalRevenue,
                    'revenueTrend' => $revenueTrend,
                    'jobs' => $totalJobs,
                    'jobsTrend' => $jobsTrend,
                    'avgOrder' => $avgOrder,
                    'avgOrderTrend' => $avgOrderTrend,
                    'availability' => $availability,
                    'availabilityTrend' => $availabilityTrend
                ],
                'charts' => [
                    'revenue' => [
                        'labels' => $revenueLabels,
                        'data' => $revenueData
                    ],
                    'categories' => [
                        'labels' => $categoryLabels,
                        'data' => $categoryData
                    ],
                    'cleaners' => [
                        'labels' => $cleanerLabels,
                        'data' => $cleanerData
                    ],
                    'retention' => [
                        'labels' => ['Returning Clients', 'New Clients'],
                        'data' => [$returningClients, $newClients]
                    ]
                ]
            ]
        ]);
    }

    public function customers()
    {
        // Logic for customers list if needed
    }

    private function calculateTrend($current, $previous)
    {
        if ($previous == 0) {
            return [
                'direction' => $current > 0 ? 'up' : 'neutral',
                'text' => $current > 0 ? '100% vs yesterday' : 'No change'
            ];
        }
        
        $percentChange = (($current - $previous) / $previous) * 100;
        $direction = $percentChange > 0 ? 'up' : ($percentChange < 0 ? 'down' : 'neutral');
        
        return [
            'direction' => $direction,
            'text' => round(abs($percentChange)) . '% vs yesterday'
        ];
    }
}
