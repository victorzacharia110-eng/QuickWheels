<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeDashboardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role === 'owner') {
            return $this->ownerIndex($request);
        }

        $user = $request->user();

        $contract = Contract::with('vehicle')
            ->where('driver_id', $user->id)
            ->latest()
            ->first();

        $payments = $contract
            ? $contract->payments()->latest()->get()
            : collect();

        return response()->json([
            'success' => true,
            'data' => [
                'contract' => $contract?->toApiResponse(),
                'payments' => $payments->map(fn($p) => $p->toApiResponse()),
            ],
        ]);
    }

    // ==================== OWNER-FACING EMPLOYEE MANAGEMENT ====================

    protected function ownerIndex(Request $request)
    {
        $ownerId = $request->user()->owner?->id;
        if (!$ownerId) {
            return response()->json(['success' => false, 'message' => 'Owner profile not found'], 403);
        }

        $employees = Employee::where('owner_id', $ownerId)
            ->with('vehicle')  // Load the vehicle relationship
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $employees->map(function($employee) {
                $data = $employee->toApiResponse();
                // Ensure vehicle_name is included
                $data['vehicle_name'] = $employee->vehicle ? $employee->vehicle->name : null;
                return $data;
            }),
        ]);
    }

    public function store(Request $request)
    {
        $ownerId = $request->user()->owner?->id;
        if (!$ownerId) {
            return response()->json(['success' => false, 'message' => 'Owner profile not found'], 403);
        }

        $input = $request->all();
        // Convert empty strings to null for nullable fields
        foreach (['phone', 'email', 'address', 'nida_number', 'license_number', 'department', 'position', 'salary', 'shift'] as $field) {
            if (isset($input[$field]) && $input[$field] === '') {
                $input[$field] = null;
            }
        }

        $validator = Validator::make($input, [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:employees,email',
            'address' => 'nullable|string',
            'nida_number' => 'nullable|string|max:20|unique:employees,nida_number',
            'license_number' => 'nullable|string|max:50|unique:employees,license_number',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'salary' => 'nullable|numeric|min:0',
            'shift' => 'nullable|string|max:50',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['owner_id'] = $ownerId;
        $data['status'] = 'active';

        try {
            $employee = Employee::create($data);

            if (!empty($data['vehicle_id'])) {
                $employee->assignVehicle($data['vehicle_id']);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create employee: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully',
            'data' => $employee->fresh()->toApiResponseWithVehicle(),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $ownerId = $request->user()->owner?->id;
        $employee = Employee::where('owner_id', $ownerId)->with('vehicle')->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatEmployeeWithVehicle($employee),
        ]);
    }

    public function update(Request $request, $id)
    {
        $ownerId = $request->user()->owner?->id;
        $employee = Employee::where('owner_id', $ownerId)->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:employees,email,' . $id,
            'address' => 'nullable|string',
            'nida_number' => 'nullable|string|max:20|unique:employees,nida_number,' . $id,
            'license_number' => 'nullable|string|max:50|unique:employees,license_number,' . $id,
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'salary' => 'nullable|numeric|min:0',
            'shift' => 'nullable|string|max:50',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
        // Handle vehicle assignment
        if (isset($data['vehicle_id'])) {
            if ($data['vehicle_id']) {
                $employee->assignVehicle($data['vehicle_id']);
            } else {
                $employee->removeVehicle();
            }
            unset($data['vehicle_id']);
        }

        $employee->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
            'data' => $this->formatEmployeeWithVehicle($employee->fresh()->load('vehicle')),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $ownerId = $request->user()->owner?->id;
        $employee = Employee::where('owner_id', $ownerId)->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully',
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $ownerId = $request->user()->owner?->id;
        $employee = Employee::where('owner_id', $ownerId)->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        $employee->toggleStatus();

        return response()->json([
            'success' => true,
            'message' => 'Employee status toggled successfully',
            'data' => $this->formatEmployeeWithVehicle($employee->fresh()->load('vehicle')),
        ]);
    }

    public function assignVehicle(Request $request, $id)
    {
        $ownerId = $request->user()->owner?->id;
        $employee = Employee::where('owner_id', $ownerId)->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $employee->assignVehicle($request->vehicle_id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vehicle assigned successfully',
            'data' => $this->formatEmployeeWithVehicle($employee->fresh()->load('vehicle')),
        ]);
    }

    public function removeVehicle(Request $request, $id)
    {
        $ownerId = $request->user()->owner?->id;
        $employee = Employee::where('owner_id', $ownerId)->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        $employee->removeVehicle();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle removed from employee successfully',
            'data' => $this->formatEmployeeWithVehicle($employee->fresh()->load('vehicle')),
        ]);
    }

    public function stats(Request $request)
    {
        $ownerId = $request->user()->owner?->id;

        if ($ownerId) {
            $stats = Employee::where('owner_id', $ownerId)->get()->pipe(fn($employees) => [
                'total' => $employees->count(),
                'active' => $employees->where('status', 'active')->count(),
                'inactive' => $employees->where('status', 'inactive')->count(),
                'with_vehicles' => $employees->whereNotNull('vehicle_id')->count(),
                'without_vehicles' => $employees->whereNull('vehicle_id')->count(),
            ]);
        } else {
            $stats = Employee::getStats();
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function withVehicles(Request $request)
    {
        $ownerId = $request->user()->owner?->id;

        $employees = Employee::where('owner_id', $ownerId)
            ->whereNotNull('vehicle_id')
            ->with('vehicle')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $employees->map(fn($e) => $this->formatEmployeeWithVehicle($e)),
        ]);
    }

    public function withoutVehicles(Request $request)
    {
        $ownerId = $request->user()->owner?->id;

        $employees = Employee::where('owner_id', $ownerId)
            ->whereNull('vehicle_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $employees->map(fn($e) => $this->formatEmployeeWithVehicle($e)),
        ]);
    }

    public function getByEmail(Request $request, $email)
    {
        $ownerId = $request->user()->owner?->id;
        $employee = Employee::where('owner_id', $ownerId)
            ->where('email', $email)
            ->with('vehicle')
            ->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->formatEmployeeWithVehicle($employee)]);
    }

    public function getByName(Request $request, $name)
    {
        $ownerId = $request->user()->owner?->id;
        $employee = Employee::where('owner_id', $ownerId)
            ->where('name', 'like', "%{$name}%")
            ->with('vehicle')
            ->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->formatEmployeeWithVehicle($employee)]);
    }

    public function dashboard(Request $request)
    {
        $ownerId = $request->user()->owner?->id;

        $employees = Employee::where('owner_id', $ownerId)->with('vehicle')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total' => $employees->count(),
                    'active' => $employees->where('status', 'active')->count(),
                    'inactive' => $employees->where('status', 'inactive')->count(),
                    'with_vehicles' => $employees->whereNotNull('vehicle_id')->count(),
                    'without_vehicles' => $employees->whereNull('vehicle_id')->count(),
                ],
                'employees' => $employees->map(fn($e) => $this->formatEmployeeWithVehicle($e)),
            ],
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Format employee data with vehicle information
     */
    private function formatEmployeeWithVehicle($employee)
    {
        $data = $employee->toApiResponse();
        $data['vehicle_id'] = $employee->vehicle_id;
        $data['vehicle_name'] = $employee->vehicle ? $employee->vehicle->name : null;
        return $data;
    }
}