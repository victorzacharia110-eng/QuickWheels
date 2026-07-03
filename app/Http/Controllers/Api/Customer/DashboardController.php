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
                'available_vehicles' => Vehicle::where('status', 'available')->where('is_active', true)->get()->map(fn($v) => $v->toApiResponse()),
            ],
        ]);
    }

    public function availableVehicles(Request $request)
    {
        $vehicles = Vehicle::where('status', 'available')
            ->where('is_active', true)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles->map(fn($v) => $v->toApiResponse()),
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
            'vehicle_id' => 'required|exists:vehicles,id',
            'pickup_location' => 'required|string|max:255',
            'return_location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'pickup_time' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        $booking = Booking::create([
            'customer_id' => $request->user()->id,
            'vehicle_id' => $validated['vehicle_id'],
            'owner_id' => $vehicle->owner_id,
            'pickup_location' => $validated['pickup_location'],
            'return_location' => $validated['return_location'] ?? $validated['pickup_location'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'pickup_time' => $validated['pickup_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'driver_name' => $request->user()->name,
            'driver_phone' => $request->user()->phone,
        ]);

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
