<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Employee;
use App\Models\Contract;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerDashboardController extends Controller
{
    /**
     * Get owner dashboard overview
     * GET /api/owner/dashboard
     */
    public function index(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        // Get all statistics
        $stats = $this->getStats($ownerId);
        $recentActivity = $this->getRecentActivity($ownerId);
        $chartData = $this->getChartData($ownerId);
        $topVehicles = $this->getTopVehicles($ownerId);
        $recentBookings = $this->getRecentBookings($ownerId);
        $pendingActions = $this->getPendingActions($ownerId);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_activity' => $recentActivity,
                'chart_data' => $chartData,
                'top_vehicles' => $topVehicles,
                'recent_bookings' => $recentBookings,
                'pending_actions' => $pendingActions,
            ]
        ]);
    }

    /**
     * Get dashboard statistics
     * GET /api/owner/dashboard/stats
     */
    public function stats(Request $request)
    {
        $ownerId = $request->user()->owner->id;
        $stats = $this->getStats($ownerId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get revenue statistics
     * GET /api/owner/dashboard/revenue
     */
    public function revenue(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        $revenue = [
            'total' => Payment::whereHas('booking', function($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })->where('status', 'completed')->sum('amount'),
            
            'this_month' => Payment::whereHas('booking', function($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })->where('status', 'completed')
              ->whereMonth('created_at', now()->month)
              ->sum('amount'),
            
            'this_week' => Payment::whereHas('booking', function($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })->where('status', 'completed')
              ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
              ->sum('amount'),
            
            'today' => Payment::whereHas('booking', function($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })->where('status', 'completed')
              ->whereDate('created_at', today())
              ->sum('amount'),
            
            'pending' => Payment::whereHas('booking', function($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })->where('status', 'pending')->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $revenue
        ]);
    }

    /**
     * Get recent bookings
     * GET /api/owner/dashboard/recent-bookings
     */
    public function recentBookings(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        $bookings = Booking::where('owner_id', $ownerId)
            ->with(['customer', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'customer_name' => $booking->customer?->name,
                    'vehicle_name' => $booking->vehicle?->name,
                    'total_amount' => $booking->total_amount,
                    'total_amount_formatted' => 'TSh ' . number_format($booking->total_amount, 0),
                    'status' => $booking->status,
                    'status_label' => $booking->status_label,
                    'status_color' => $booking->status_color,
                    'start_date' => $booking->start_date?->toDateString(),
                    'end_date' => $booking->end_date?->toDateString(),
                    'created_at' => $booking->created_at?->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Get monthly chart data
     * GET /api/owner/dashboard/chart
     */
    public function chart(Request $request)
    {
        $ownerId = $request->user()->owner->id;
        $months = 12;

        $chartData = $this->getChartData($ownerId, $months);

        return response()->json([
            'success' => true,
            'data' => $chartData
        ]);
    }

    /**
     * Get vehicle performance
     * GET /api/owner/dashboard/vehicle-performance
     */
    public function vehiclePerformance(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        $vehicles = Vehicle::where('owner_id', $ownerId)
            ->withCount(['bookings as total_bookings' => function($q) {
                $q->where('status', 'completed');
            }])
            ->withSum(['bookings as total_earnings' => function($q) {
                $q->where('status', 'completed');
            }], 'total_amount')
            ->get()
            ->map(function($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                    'type' => $vehicle->type,
                    'registration_number' => $vehicle->registration_number,
                    'total_bookings' => $vehicle->total_bookings ?? 0,
                    'total_earnings' => $vehicle->total_earnings ?? 0,
                    'total_earnings_formatted' => 'TSh ' . number_format($vehicle->total_earnings ?? 0, 0),
                    'status' => $vehicle->status,
                    'status_color' => $vehicle->status === 'available' ? '#00E5FF' : '#ff6b6b',
                    'rating' => $vehicle->reviews()->avg('rating') ?? 0,
                ];
            })
            ->sortByDesc('total_earnings')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Get dashboard statistics
     */
    private function getStats($ownerId)
    {
        // Vehicle statistics
        $totalVehicles = Vehicle::where('owner_id', $ownerId)->count();
        $availableVehicles = Vehicle::where('owner_id', $ownerId)->where('status', 'available')->count();
        $rentedVehicles = Vehicle::where('owner_id', $ownerId)->where('status', 'rented')->count();
        $maintenanceVehicles = Vehicle::where('owner_id', $ownerId)->where('status', 'maintenance')->count();

        // Booking statistics
        $totalBookings = Booking::where('owner_id', $ownerId)->count();
        $pendingBookings = Booking::where('owner_id', $ownerId)->where('status', 'pending')->count();
        $confirmedBookings = Booking::where('owner_id', $ownerId)->where('status', 'confirmed')->count();
        $activeBookings = Booking::where('owner_id', $ownerId)->where('status', 'active')->count();
        $completedBookings = Booking::where('owner_id', $ownerId)->where('status', 'completed')->count();
        $cancelledBookings = Booking::where('owner_id', $ownerId)->where('status', 'cancelled')->count();

        // Employee statistics
        $totalEmployees = Employee::where('owner_id', $ownerId)->count();
        $activeEmployees = Employee::where('owner_id', $ownerId)->where('status', 'active')->count();

        // Revenue statistics
        $totalRevenue = Payment::whereHas('booking', function($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->where('status', 'completed')->sum('amount');

        $monthlyRevenue = Payment::whereHas('booking', function($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->where('status', 'completed')
          ->whereMonth('created_at', now()->month)
          ->sum('amount');

        $todayRevenue = Payment::whereHas('booking', function($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->where('status', 'completed')
          ->whereDate('created_at', today())
          ->sum('amount');

        // Contract statistics
        $totalContracts = Contract::where('owner_id', $ownerId)->count();
        $activeContracts = Contract::where('owner_id', $ownerId)->where('status', 'active')->count();

        return [
            'vehicles' => [
                'total' => $totalVehicles,
                'available' => $availableVehicles,
                'rented' => $rentedVehicles,
                'maintenance' => $maintenanceVehicles,
                'utilization_rate' => $totalVehicles > 0 
                    ? round(($rentedVehicles / $totalVehicles) * 100, 1) 
                    : 0,
            ],
            'bookings' => [
                'total' => $totalBookings,
                'pending' => $pendingBookings,
                'confirmed' => $confirmedBookings,
                'active' => $activeBookings,
                'completed' => $completedBookings,
                'cancelled' => $cancelledBookings,
                'completion_rate' => $totalBookings > 0 
                    ? round(($completedBookings / $totalBookings) * 100, 1) 
                    : 0,
            ],
            'employees' => [
                'total' => $totalEmployees,
                'active' => $activeEmployees,
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'total_formatted' => 'TSh ' . number_format($totalRevenue, 0),
                'this_month' => $monthlyRevenue,
                'this_month_formatted' => 'TSh ' . number_format($monthlyRevenue, 0),
                'today' => $todayRevenue,
                'today_formatted' => 'TSh ' . number_format($todayRevenue, 0),
            ],
            'contracts' => [
                'total' => $totalContracts,
                'active' => $activeContracts,
            ],
        ];
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity($ownerId)
    {
        $activities = [];

        // Recent bookings
        $recentBookings = Booking::where('owner_id', $ownerId)
            ->with(['customer', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentBookings as $booking) {
            $activities[] = [
                'type' => 'booking',
                'icon' => 'fa-solid fa-calendar-check',
                'message' => "New booking #{$booking->booking_number} from {$booking->customer?->name} for {$booking->vehicle?->name}",
                'status' => $booking->status,
                'status_label' => $booking->status_label,
                'status_color' => $booking->status_color,
                'time' => $booking->created_at?->diffForHumans(),
                'datetime' => $booking->created_at?->toDateTimeString(),
                'id' => $booking->id,
            ];
        }

        // Recent payments
        $recentPayments = Payment::whereHas('booking', function($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->with(['booking.customer'])
          ->orderBy('created_at', 'desc')
          ->limit(5)
          ->get();

        foreach ($recentPayments as $payment) {
            $activities[] = [
                'type' => 'payment',
                'icon' => 'fa-solid fa-money-bill-wave',
                'message' => "Payment of TSh " . number_format($payment->amount, 0) . " from {$payment->booking?->customer?->name}",
                'status' => $payment->status,
                'status_label' => $payment->status_label,
                'status_color' => $payment->status_color,
                'time' => $payment->created_at?->diffForHumans(),
                'datetime' => $payment->created_at?->toDateTimeString(),
                'id' => $payment->id,
            ];
        }

        // Sort by datetime descending
        usort($activities, function($a, $b) {
            return strtotime($b['datetime']) - strtotime($a['datetime']);
        });

        // Return only first 10
        return array_slice($activities, 0, 10);
    }

    /**
     * Get chart data for the last X months
     */
    private function getChartData($ownerId, $months = 12)
    {
        $chartData = [
            'labels' => [],
            'bookings' => [],
            'revenue' => [],
        ];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthName = $month->format('M Y');
            
            $chartData['labels'][] = $monthName;

            // Bookings count for this month
            $bookingsCount = Booking::where('owner_id', $ownerId)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
            
            $chartData['bookings'][] = $bookingsCount;

            // Revenue for this month
            $revenue = Payment::whereHas('booking', function($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })->where('status', 'completed')
              ->whereMonth('created_at', $month->month)
              ->whereYear('created_at', $month->year)
              ->sum('amount');
            
            $chartData['revenue'][] = $revenue;
        }

        return $chartData;
    }

    /**
     * Get top performing vehicles
     */
    private function getTopVehicles($ownerId)
    {
        return Vehicle::where('owner_id', $ownerId)
            ->withCount(['bookings as total_bookings' => function($q) {
                $q->where('status', 'completed');
            }])
            ->withSum(['bookings as total_earnings' => function($q) {
                $q->where('status', 'completed');
            }], 'total_amount')
            ->orderBy('total_earnings', 'desc')
            ->limit(5)
            ->get()
            ->map(function($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                    'type' => $vehicle->type,
                    'registration_number' => $vehicle->registration_number,
                    'total_bookings' => $vehicle->total_bookings ?? 0,
                    'total_earnings' => $vehicle->total_earnings ?? 0,
                    'total_earnings_formatted' => 'TSh ' . number_format($vehicle->total_earnings ?? 0, 0),
                    'status' => $vehicle->status,
                    'status_color' => $vehicle->status === 'available' ? '#00E5FF' : '#ff6b6b',
                ];
            });
    }

    /**
     * Get recent bookings with details
     */
    private function getRecentBookings($ownerId)
    {
        return Booking::where('owner_id', $ownerId)
            ->with(['customer', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'customer_name' => $booking->customer?->name,
                    'customer_phone' => $booking->customer?->phone,
                    'vehicle_name' => $booking->vehicle?->name,
                    'total_amount' => $booking->total_amount,
                    'total_amount_formatted' => 'TSh ' . number_format($booking->total_amount, 0),
                    'status' => $booking->status,
                    'status_label' => $booking->status_label,
                    'status_color' => $booking->status_color,
                    'start_date' => $booking->start_date?->toDateString(),
                    'end_date' => $booking->end_date?->toDateString(),
                    'created_at' => $booking->created_at?->toDateTimeString(),
                    'time_ago' => $booking->created_at?->diffForHumans(),
                ];
            });
    }

    /**
     * Get pending actions that need attention
     */
    private function getPendingActions($ownerId)
    {
        $actions = [];

        // Pending bookings
        $pendingBookings = Booking::where('owner_id', $ownerId)
            ->where('status', 'pending')
            ->count();

        if ($pendingBookings > 0) {
            $actions[] = [
                'type' => 'pending_bookings',
                'icon' => 'fa-solid fa-clock',
                'title' => 'Pending Approvals',
                'message' => "You have {$pendingBookings} pending booking" . ($pendingBookings > 1 ? 's' : '') . " awaiting approval",
                'count' => $pendingBookings,
                'priority' => $pendingBookings > 5 ? 'high' : 'medium',
                'link' => '/owner/bookings?status=pending',
            ];
        }

        // Vehicles in maintenance
        $maintenanceVehicles = Vehicle::where('owner_id', $ownerId)
            ->where('status', 'maintenance')
            ->count();

        if ($maintenanceVehicles > 0) {
            $actions[] = [
                'type' => 'maintenance',
                'icon' => 'fa-solid fa-wrench',
                'title' => 'Vehicles in Maintenance',
                'message' => "{$maintenanceVehicles} vehicle" . ($maintenanceVehicles > 1 ? 's are' : ' is') . " currently in maintenance",
                'count' => $maintenanceVehicles,
                'priority' => 'medium',
                'link' => '/owner/vehicles?status=maintenance',
            ];
        }

        // Low vehicle availability
        $availableVehicles = Vehicle::where('owner_id', $ownerId)
            ->where('status', 'available')
            ->count();

        $totalVehicles = Vehicle::where('owner_id', $ownerId)->count();

        if ($totalVehicles > 0 && ($availableVehicles / $totalVehicles) < 0.2) {
            $actions[] = [
                'type' => 'low_availability',
                'icon' => 'fa-solid fa-triangle-exclamation',
                'title' => 'Low Vehicle Availability',
                'message' => "Only {$availableVehicles} vehicle" . ($availableVehicles > 1 ? 's are' : ' is') . " available out of {$totalVehicles}",
                'count' => $availableVehicles,
                'priority' => 'high',
                'link' => '/owner/vehicles',
            ];
        }

        // Active contracts with overdue payments
        $overdueContracts = Contract::where('owner_id', $ownerId)
            ->where('status', 'active')
            ->where('remaining_amount', '>', 0)
            ->where('end_date', '<', now())
            ->count();

        if ($overdueContracts > 0) {
            $actions[] = [
                'type' => 'overdue_contracts',
                'icon' => 'fa-solid fa-exclamation-circle',
                'title' => 'Overdue Contracts',
                'message' => "{$overdueContracts} contract" . ($overdueContracts > 1 ? 's are' : ' is') . " overdue for payment",
                'count' => $overdueContracts,
                'priority' => 'high',
                'link' => '/owner/contracts?status=overdue',
            ];
        }

        return $actions;
    }

    // ==================== AI TOGGLE ====================

    /**
     * Toggle AI features on/off for the owner
     * POST /api/owner/ai/toggle
     */
    public function toggleAi(Request $request)
    {
        $owner = $request->user()->owner;

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'Owner profile not found'], 404);
        }

        $newState = $owner->toggleAi();

        return response()->json([
            'success' => true,
            'message' => $newState ? 'AI features enabled' : 'AI features disabled',
            'data' => [
                'ai_enabled' => $newState,
            ],
        ]);
    }

    /**
     * Get AI feature status
     * GET /api/owner/ai/status
     */
    public function aiStatus(Request $request)
    {
        $owner = $request->user()->owner;

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'Owner profile not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'ai_enabled' => $owner->isAiEnabled(),
            ],
        ]);
    }
}