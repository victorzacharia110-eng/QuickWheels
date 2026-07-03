<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleLocation;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class GpsController extends Controller
{
    public function updateLocation(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
        ]);

        $location = VehicleLocation::create([
            'vehicle_id' => $validated['vehicle_id'],
            'employee_id' => $request->user()->employee?->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'speed' => $validated['speed'] ?? null,
            'heading' => $validated['heading'] ?? null,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated',
            'data' => $location,
        ]);
    }

    public function getLatestLocation($vehicleId)
    {
        $vehicle = Vehicle::find($vehicleId);
        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Vehicle not found'], 404);
        }

        $location = VehicleLocation::latestForVehicle($vehicleId)->first();

        return response()->json([
            'success' => true,
            'data' => $location ? [
                'id' => $location->id,
                'vehicle_id' => $location->vehicle_id,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'speed' => $location->speed,
                'heading' => $location->heading,
                'recorded_at' => $location->recorded_at,
            ] : null,
        ]);
    }

    public function getVehicleHistory(Request $request, $vehicleId)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        $query = VehicleLocation::where('vehicle_id', $vehicleId);

        if ($validated['from'] ?? null) {
            $query->where('recorded_at', '>=', $validated['from']);
        }
        if ($validated['to'] ?? null) {
            $query->where('recorded_at', '<=', $validated['to']);
        }

        $locations = $query->orderBy('recorded_at', 'desc')
            ->limit($validated['limit'] ?? 100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations->map(fn($l) => [
                'id' => $l->id,
                'vehicle_id' => $l->vehicle_id,
                'employee_id' => $l->employee_id,
                'latitude' => $l->latitude,
                'longitude' => $l->longitude,
                'speed' => $l->speed,
                'heading' => $l->heading,
                'recorded_at' => $l->recorded_at,
            ]),
        ]);
    }

    public function getAllLatest(Request $request)
    {
        $vehicleIds = Vehicle::where('owner_id', $request->user()->owner?->id)
            ->where('is_active', true)
            ->pluck('id');

        $locations = VehicleLocation::whereIn('vehicle_id', $vehicleIds)
            ->selectRaw('vehicle_id, MAX(recorded_at) as max_time')
            ->groupBy('vehicle_id')
            ->get()
            ->map(fn($row) => VehicleLocation::where('vehicle_id', $row->vehicle_id)
                ->where('recorded_at', $row->max_time)
                ->first()
            )
            ->filter();

        return response()->json([
            'success' => true,
            'data' => $locations->map(fn($l) => [
                'id' => $l->id,
                'vehicle_id' => $l->vehicle_id,
                'vehicle_name' => $l->vehicle?->name,
                'latitude' => $l->latitude,
                'longitude' => $l->longitude,
                'speed' => $l->speed,
                'heading' => $l->heading,
                'recorded_at' => $l->recorded_at,
            ])->values(),
        ]);
    }
}
