<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class RideRequestController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'No employee profile found'], 404);
        }

        $bookings = Booking::where('assigned_driver_id', $employee->id)
            ->whereIn('status', ['requested', 'accepted', 'en_route', 'in_progress'])
            ->with(['customer', 'vehicle'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings->map(fn($b) => $b->toApiResponse()),
        ]);
    }

    public function history(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'No employee profile found'], 404);
        }

        $bookings = Booking::where('assigned_driver_id', $employee->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->with(['customer', 'vehicle'])
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings->map(fn($b) => $b->toApiResponse()),
        ]);
    }

    public function accept(Request $request, $id)
    {
        $employee = $request->user()->employee;

        $booking = Booking::where('assigned_driver_id', $employee?->id)
            ->where('status', 'requested')
            ->find($id);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Ride request not found'], 404);
        }

        try {
            $booking->accept($employee->id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ride accepted',
            'data' => $booking->fresh()->toApiResponse(),
        ]);
    }

    public function enRoute(Request $request, $id)
    {
        $employee = $request->user()->employee;

        $booking = Booking::where('assigned_driver_id', $employee?->id)
            ->where('status', 'accepted')
            ->find($id);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Ride not found'], 404);
        }

        try {
            $booking->markEnRoute();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'En route to pickup',
            'data' => $booking->fresh()->toApiResponse(),
        ]);
    }

    public function start(Request $request, $id)
    {
        $employee = $request->user()->employee;

        $booking = Booking::where('assigned_driver_id', $employee?->id)
            ->where('status', 'en_route')
            ->find($id);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Ride not found'], 404);
        }

        try {
            $booking->startRide();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ride started',
            'data' => $booking->fresh()->toApiResponse(),
        ]);
    }

    public function complete(Request $request, $id)
    {
        $employee = $request->user()->employee;

        $booking = Booking::where('assigned_driver_id', $employee?->id)
            ->where('status', 'in_progress')
            ->find($id);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Ride not found'], 404);
        }

        $fare = $request->input('fare');

        try {
            $booking->completeRide($fare);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ride completed',
            'data' => $booking->fresh()->toApiResponse(),
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $employee = $request->user()->employee;

        $booking = Booking::where('assigned_driver_id', $employee?->id)
            ->whereIn('status', ['requested', 'accepted', 'en_route'])
            ->find($id);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Ride not found'], 404);
        }

        try {
            $booking->cancel($request->reason ?? 'Cancelled by driver');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ride cancelled',
            'data' => $booking->fresh()->toApiResponse(),
        ]);
    }
}
