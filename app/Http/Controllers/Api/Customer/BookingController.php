<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::where('customer_id', $request->user()->id)
            ->with('vehicle')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings->map(fn($b) => $b->toApiResponse()),
        ]);
    }

    public function store(Request $request)
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

    public function show(Request $request, $id)
    {
        $booking = Booking::where('customer_id', $request->user()->id)
            ->with('vehicle')
            ->find($id);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $booking->toApiResponse(),
        ]);
    }

    public function cancel(Request $request, $id)
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
            'message' => 'Booking cancelled',
            'data' => $booking->fresh()->toApiResponse(),
        ]);
    }

    public function approve(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        try {
            $booking->confirm($request->user()->employee?->id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking approved',
            'data' => $booking->fresh()->load('vehicle')->toApiResponse(),
        ]);
    }

    public function reject(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        try {
            $booking->cancel($request->reason ?? 'Rejected by owner');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking rejected',
            'data' => $booking->fresh()->toApiResponse(),
        ]);
    }

    public function complete(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        try {
            $booking->complete();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking completed',
            'data' => $booking->fresh()->toApiResponse(),
        ]);
    }
}
