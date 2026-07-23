<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::query();

        if ($request->user() && $request->user()->role === 'owner') {
            $ownerId = $request->user()->owner->id;
            if ($ownerId) {
                $query->where('owner_id', $ownerId);
            }
        }

        if ($request->type && $request->type !== 'All') {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $vehicles = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles->map(fn($v) => $v->toApiResponse()),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:Motorcycle,Bajaji,Car,SUV',
            'registration' => 'required|string|max:50',
            'color' => 'nullable|string|max:50',
            'chassis_number' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ownerId = $request->user()->owner->id;

        $data = $validator->validated();
        $data['owner_id'] = $ownerId;
        $data['status'] = 'available';

        $vehicle = Vehicle::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle created successfully',
            'data' => $vehicle->toApiResponse(),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found',
            ], 404);
        }

        if ($request->user() && $request->user()->role === 'owner') {
            $ownerId = $request->user()->owner->id;
            if ($ownerId && $vehicle->owner_id && $vehicle->owner_id !== $ownerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $vehicle->toApiResponse(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found',
            ], 404);
        }

        $ownerId = $request->user()->owner->id;
        if (!$ownerId || $vehicle->owner_id !== $ownerId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:Motorcycle,Bajaji,Car,SUV',
            'registration' => 'sometimes|string|max:50',
            'color' => 'nullable|string|max:50',
            'chassis_number' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'price' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:available,on_contract,maintenance,rented,assigned',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'tags' => 'nullable|array',
            'next_service_date' => 'nullable|date|after_or_equal:today',
            'next_service_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $vehicle->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle->fresh()->toApiResponse(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found',
            ], 404);
        }

        $ownerId = $request->user()->owner->id;
        if (!$ownerId || $vehicle->owner_id !== $ownerId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully',
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found',
            ], 404);
        }

        $ownerId = $request->user()->owner->id;
        if (!$ownerId || $vehicle->owner_id !== $ownerId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:available,on_contract,maintenance,rented,assigned',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $vehicle->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle status updated successfully',
            'data' => $vehicle->fresh()->toApiResponse(),
        ]);
    }

    public function stats(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        return response()->json([
            'success' => true,
            'data' => Vehicle::getStats($ownerId),
        ]);
    }

    public function scheduleService(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);
        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Vehicle not found'], 404);
        }

        $ownerId = $request->user()->owner->id;
        if ($vehicle->owner_id !== $ownerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'service_date' => 'required|date|after_or_equal:today',
            'technician_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $vehicle->update([
            'next_service_date' => $data['service_date'],
            'next_service_notes' => $data['notes'] ?? null,
        ]);

        $report = Maintenance::create([
            'report_number' => 'MT-' . date('Y') . '-' . str_pad(Maintenance::whereYear('created_at', date('Y'))->count() + 1, 3, '0', STR_PAD_LEFT),
            'employee_id' => $data['technician_id'] ?? $vehicle->technician?->id,
            'vehicle_id' => $vehicle->id,
            'owner_id' => $ownerId,
            'title' => 'Scheduled Service — ' . $vehicle->name,
            'description' => $data['notes'] ?? 'Scheduled maintenance service for ' . $vehicle->name,
            'priority' => 'medium',
            'status' => 'pending',
            'vehicle_mileage' => $vehicle->mileage,
            'next_service_date' => $data['service_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service scheduled and maintenance report created',
            'data' => [
                'vehicle' => $vehicle->fresh()->toApiResponse(),
                'report' => $report->toApiResponse(),
            ],
        ], 201);
    }
}
