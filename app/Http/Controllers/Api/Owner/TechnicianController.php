<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TechnicianController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        $technicians = Employee::where('owner_id', $ownerId)
            ->where('position', 'Technician')
            ->with('vehicle')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $technicians->map(fn($t) => $this->formatTechnician($t)),
        ]);
    }

    public function store(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        $input = array_map(fn($v) => $v === '' ? null : $v, $request->all());

        $validator = Validator::make($input, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'workshop_address' => 'nullable|string',
            'workshop_latitude' => 'nullable|numeric|between:-90,90',
            'workshop_longitude' => 'nullable|numeric|between:-180,180',
            'nida_number' => 'nullable|string|max:20|unique:employees,nida_number',
            'license_number' => 'nullable|string|max:50|unique:employees,license_number',
            'salary' => 'nullable|numeric|min:0',
            'shift' => 'nullable|string|max:50',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'can_drive' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Default password = technician's name, lowercased, spaces removed
        $plainPassword = Str::lower(str_replace(' ', '', $data['name']));

        // Create User account with technician role
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($plainPassword),
            'phone' => $data['phone'] ?? null,
            'role' => 'technician',
            'can_drive' => !empty($data['can_drive']),
            'is_active' => true,
        ]);

        try {
            $employee = Employee::create([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'],
                'address' => $data['address'] ?? null,
                'workshop_address' => $data['workshop_address'] ?? null,
                'workshop_latitude' => $data['workshop_latitude'] ?? null,
                'workshop_longitude' => $data['workshop_longitude'] ?? null,
                'nida_number' => $data['nida_number'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'department' => 'Maintenance',
                'position' => 'Technician',
                'salary' => $data['salary'] ?? null,
                'shift' => $data['shift'] ?? null,
                'owner_id' => $ownerId,
                'user_id' => $user->id,
                'status' => 'active',
            ]);

            if (!empty($data['vehicle_id'])) {
                $employee->assignVehicle($data['vehicle_id']);
            }

            if (!empty($data['can_drive'])) {
                Employee::create([
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'],
                    'address' => $data['address'] ?? null,
                    'nida_number' => $data['nida_number'] ?? null,
                    'license_number' => $data['license_number'] ?? null,
                    'department' => 'Operations',
                    'position' => 'Driver',
                    'salary' => $data['salary'] ?? null,
                    'shift' => $data['shift'] ?? null,
                    'owner_id' => $ownerId,
                    'user_id' => $user->id,
                    'status' => 'active',
                ]);
            }
        } catch (\Exception $e) {
            $user->delete();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create technician: ' . $e->getMessage(),
            ], 500);
        }

        $responseData = $this->formatTechnician($employee->fresh()->load('vehicle'));
        $responseData['password'] = $plainPassword;

        return response()->json([
            'success' => true,
            'message' => 'Technician created successfully',
            'data' => $responseData,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;
        $technician = Employee::where('owner_id', $ownerId)
            ->where('position', 'Technician')
            ->with('vehicle')
            ->find($id);

        if (!$technician) {
            return response()->json(['success' => false, 'message' => 'Technician not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatTechnician($technician),
        ]);
    }

    public function update(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;
        $technician = Employee::where('owner_id', $ownerId)
            ->where('position', 'Technician')
            ->find($id);

        if (!$technician) {
            return response()->json(['success' => false, 'message' => 'Technician not found'], 404);
        }

        $input = array_map(fn($v) => $v === '' ? null : $v, $request->all());
        $validator = Validator::make($input, [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:users,email,' . ($technician->user_id ?? 'NULL') . '|unique:employees,email,' . $id,
            'address' => 'nullable|string',
            'nida_number' => 'nullable|string|max:20|unique:employees,nida_number,' . $id,
            'license_number' => 'nullable|string|max:50|unique:employees,license_number,' . $id,
            'salary' => 'nullable|numeric|min:0',
            'shift' => 'nullable|string|max:50',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'workshop_address' => 'nullable|string',
            'workshop_latitude' => 'nullable|numeric|between:-90,90',
            'workshop_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        try {
            if (array_key_exists('vehicle_id', $data)) {
                if (!empty($data['vehicle_id'])) {
                    $technician->assignVehicle($data['vehicle_id']);
                } else {
                    $technician->removeVehicle();
                }
                unset($data['vehicle_id']);
            }

            $technician->update($data);

            if ($technician->user_id) {
                $userData = [];
                if (isset($data['name'])) $userData['name'] = $data['name'];
                if (isset($data['email'])) $userData['email'] = $data['email'];
                if (isset($data['phone'])) $userData['phone'] = $data['phone'];
                if (!empty($userData)) {
                    User::where('id', $technician->user_id)->update($userData);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Technician updated successfully',
                'data' => $this->formatTechnician($technician->fresh()->load('vehicle')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update technician: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;
        $technician = Employee::where('owner_id', $ownerId)
            ->where('position', 'Technician')
            ->find($id);

        if (!$technician) {
            return response()->json(['success' => false, 'message' => 'Technician not found'], 404);
        }

        $userId = $technician->user_id;
        $technician->delete();

        if ($userId) {
            User::where('id', $userId)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Technician deleted successfully',
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;
        $technician = Employee::where('owner_id', $ownerId)
            ->where('position', 'Technician')
            ->find($id);

        if (!$technician) {
            return response()->json(['success' => false, 'message' => 'Technician not found'], 404);
        }

        $technician->toggleStatus();

        return response()->json([
            'success' => true,
            'message' => 'Technician status toggled successfully',
            'data' => $this->formatTechnician($technician->fresh()->load('vehicle')),
        ]);
    }

    public function stats(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        $technicians = Employee::where('owner_id', $ownerId)
            ->where('position', 'Technician')
            ->get();

        $technicianIds = $technicians->pluck('id');

        $stats = [
            'total' => $technicians->count(),
            'active' => $technicians->where('status', 'active')->count(),
            'inactive' => $technicians->where('status', 'inactive')->count(),
            'with_vehicles' => $technicians->whereNotNull('vehicle_id')->count(),
            'total_reports' => Maintenance::whereIn('employee_id', $technicianIds)->count(),
            'pending_reports' => Maintenance::whereIn('employee_id', $technicianIds)->where('status', 'pending')->count(),
            'completed_reports' => Maintenance::whereIn('employee_id', $technicianIds)->where('status', 'completed')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    private function formatTechnician($employee)
    {
        $maintenanceCount = Maintenance::where('employee_id', $employee->id)->count();
        $pendingCount = Maintenance::where('employee_id', $employee->id)->where('status', 'pending')->count();
        $completedCount = Maintenance::where('employee_id', $employee->id)->where('status', 'completed')->count();

        $data = $employee->toApiResponse();
        $data['vehicle_id'] = $employee->vehicle_id;
        $data['vehicle_name'] = $employee->vehicle?->name;
        $data['maintenance_stats'] = [
            'total_reports' => $maintenanceCount,
            'pending' => $pendingCount,
            'completed' => $completedCount,
        ];

        return $data;
    }
}
