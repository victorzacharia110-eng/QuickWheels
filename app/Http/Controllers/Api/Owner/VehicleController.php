<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            'year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'price' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:available,on_contract,maintenance,rented,assigned',
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
}
