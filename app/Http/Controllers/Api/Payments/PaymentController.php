<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::query();

        if ($request->user()->role === 'owner') {
            $ownerId = $request->user()->owner?->id;
            if ($ownerId) {
                $query->where('owner_id', $ownerId);
            }
        } elseif ($request->user()->role === 'employee') {
            $query->where('driver_id', $request->user()->id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->method) {
            $query->where('method', $request->method);
        }

        if ($request->contract_id) {
            $query->where('contract_id', $request->contract_id);
        }

        if ($request->driver_id) {
            $query->where('driver_id', $request->driver_id);
        }

        $payments = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $payments->map(fn($p) => $p->toApiResponse()),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'nullable|exists:contracts,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'driver_name' => 'required|string|max:255',
            'driver_id' => 'nullable|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'nullable|string|max:50',
            'status' => 'nullable|in:paid,pending,failed,completed,cancelled,refunded',
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->user()->role === 'owner') {
            $data['owner_id'] = $request->user()->owner?->id;
        }

        $payment = Payment::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'data' => $payment->fresh()->toApiResponse(),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payment->toApiResponse(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|numeric|min:0',
            'method' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:paid,pending,failed,completed,cancelled,refunded',
            'date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payment->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data' => $payment->fresh()->toApiResponse(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully',
        ]);
    }

    public function approve(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending payments can be approved',
            ], 400);
        }

        $payment->approve($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Payment approved successfully',
            'data' => $payment->fresh()->toApiResponse(),
        ]);
    }

    public function reject(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending payments can be rejected',
            ], 400);
        }

        $payment->reject();

        return response()->json([
            'success' => true,
            'message' => 'Payment rejected successfully',
            'data' => $payment->fresh()->toApiResponse(),
        ]);
    }

    public function stats(Request $request)
    {
        $ownerId = $request->user()->owner?->id;

        return response()->json([
            'success' => true,
            'data' => Payment::getStats($ownerId),
        ]);
    }

    public function dashboard(Request $request)
    {
        $ownerId = $request->user()->owner?->id;

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => Payment::getStats($ownerId),
                'by_method' => Payment::getGroupedByMethod($ownerId),
                'recent' => Payment::when($ownerId, fn($q) => $q->where('owner_id', $ownerId))
                    ->latest()->limit(10)->get()->map(fn($p) => $p->toApiResponse()),
            ],
        ]);
    }

    public function getByMethod(Request $request)
    {
        $ownerId = $request->user()->owner?->id;

        return response()->json([
            'success' => true,
            'data' => Payment::getGroupedByMethod($ownerId),
        ]);
    }

    public function getByContract(Request $request, $contractId)
    {
        return response()->json([
            'success' => true,
            'data' => Payment::getByContractWithTotals($contractId),
        ]);
    }

    public function getByDriver(Request $request, $driverId)
    {
        $payments = Payment::where('driver_id', $driverId)
            ->latest()->get()->map(fn($p) => $p->toApiResponse());

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function getByDriverName(Request $request, $driverName)
    {
        $payments = Payment::byDriverName($driverName)
            ->latest()->get()->map(fn($p) => $p->toApiResponse());

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function getByStatus(Request $request, $status)
    {
        $ownerId = $request->user()->owner?->id;

        $payments = Payment::when($ownerId, fn($q) => $q->where('owner_id', $ownerId))
            ->where('status', $status)
            ->latest()->get()->map(fn($p) => $p->toApiResponse());

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }
}
