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
            'vehicle_id' => 'required|exists:vehicles,id',
            'pickup_location' => 'required|string|max:255',
            'return_location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'pickup_time' => 'nullable',
            'return_time' => 'nullable',
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
            'return_time' => $validated['return_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'driver_name' => $request->user()->name,
            'driver_phone' => $request->user()->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => $booking->fresh()->load('vehicle')->toApiResponse(),
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
