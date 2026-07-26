<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $bookings = Booking::where('customer_id', $userId)->latest()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_bookings' => $bookings->count(),
                    'active' => $bookings->where('status', 'active')->count(),
                    'pending' => $bookings->where('status', 'pending')->count(),
                    'completed' => $bookings->where('status', 'completed')->count(),
                    'cancelled' => $bookings->where('status', 'cancelled')->count(),
                ],
                'recent_bookings' => $bookings->take(5)->values()->map(fn($b) => $b->toApiResponse()),
                'available_vehicles' => Vehicle::whereIn('status', ['available', 'assigned'])->where('is_active', true)->get()->map(fn($v) => $v->toApiResponse()),
            ],
        ]);
    }

    public function availableVehicles(Request $request)
    {
        $vehicles = Vehicle::whereIn('status', ['available', 'assigned'])
            ->where('is_active', true)
            ->with(['employees' => function ($q) {
                $q->with('user');
            }])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles->map(function ($v) {
                $data = $v->toApiResponse();
                $driver = $v->employees->where('position', 'Driver')->first() ?? $v->employees->first();
                $data['driver'] = $driver ? [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                    'user_id' => $driver->user_id,
                ] : null;
                return $data;
            }),
        ]);
    }

    public function availableDrivers(Request $request)
    {
        $drivers = Employee::where('status', 'active')
            ->whereNotNull('vehicle_id')
            ->with('vehicle')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $drivers->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'phone' => $e->phone,
                'vehicle_id' => $e->vehicle_id,
                'vehicle_name' => $e->vehicle_name,
                'vehicle_type' => $e->vehicle?->type,
                'status' => $e->status,
            ]),
        ]);
    }

    public function nearbyDrivers(Request $request)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        $drivers = Employee::where('status', 'active')
            ->whereNotNull('vehicle_id')
            ->with('vehicle')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'phone' => $e->phone,
                'vehicle_id' => $e->vehicle_id,
                'vehicle_name' => $e->vehicle_name,
                'vehicle_type' => $e->vehicle?->type,
                'latitude' => $e->latitude ?? (-6.8 + mt_rand(-100, 100) / 1000),
                'longitude' => $e->longitude ?? (39.2 + mt_rand(-100, 100) / 1000),
                'distance' => $lat && $lng ? $this->haversine((float)$lat, (float)$lng, (float)($e->latitude ?? -6.8), (float)($e->longitude ?? 39.2)) : null,
                'status' => $e->status,
            ]);

        if ($lat && $lng) {
            $drivers = $drivers->sortBy('distance')->values();
        }

        return response()->json([
            'success' => true,
            'data' => $drivers,
        ]);
    }

    public function requestRide(Request $request)
    {
        $validated = $request->validate([
            'pickup_location' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'scheduled_at' => 'nullable|date|after_or_equal:now',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $vehicle = Vehicle::whereIn('status', ['available', 'assigned'])
            ->where('is_active', true)
            ->first();

        $booking = Booking::create([
            'customer_id' => $user->id,
            'vehicle_id' => $vehicle?->id,
            'owner_id' => $vehicle?->owner_id,
            'assigned_driver_id' => $vehicle?->employees->where('position', 'Driver')->first()?->id,
            'pickup_location' => $validated['pickup_location'],
            'destination' => $validated['destination'],
            'scheduled_at' => $validated['scheduled_at'] ?? now(),
            'start_date' => $validated['scheduled_at'] ? date('Y-m-d', strtotime($validated['scheduled_at'])) : today(),
            'pickup_time' => $validated['scheduled_at'] ? date('H:i:s', strtotime($validated['scheduled_at'])) : now()->toTimeString(),
            'notes' => $validated['notes'] ?? null,
            'status' => 'requested',
            'driver_name' => $user->name,
            'driver_phone' => $user->phone,
        ]);

        $booking->load(['vehicle', 'assignedDriver', 'owner']);

        return response()->json([
            'success' => true,
            'message' => 'Ride requested successfully',
            'data' => $booking->toApiResponse(),
        ], 201);
    }

    public function myRides(Request $request)
    {
        $bookings = Booking::where('customer_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings->map(fn($b) => $b->toApiResponse()),
        ]);
    }

    public function cancelRide(Request $request, $id)
    {
        $booking = Booking::where('customer_id', $request->user()->id)->find($id);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        try {
            $booking->cancel($request->reason ?? 'Cancelled by customer');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ride cancelled',
            'data' => $booking->fresh()->toApiResponse(),
        ]);
    }

    private function haversine($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }
}
