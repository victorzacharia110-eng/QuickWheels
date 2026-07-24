<?php

namespace App\Http\Controllers\Api\Technician;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\MaintenanceItem;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\Contract;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Technician profile not found'], 404);
        }

        $query = Maintenance::with(['vehicle', 'contract', 'items'])
            ->where('employee_id', $employee->id)
            ->latest();

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority !== '') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('report_number', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%")
                         ->orWhere('registration_number', 'like', "%{$search}%");
                  });
            });
        }

        $maintenances = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $maintenances->map(fn($m) => $m->toApiResponse()),
            'pagination' => [
                'current_page' => $maintenances->currentPage(),
                'last_page' => $maintenances->lastPage(),
                'per_page' => $maintenances->perPage(),
                'total' => $maintenances->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Technician profile not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'diagnosed_issues' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'vehicle_mileage' => 'nullable|numeric|min:0',
            'estimated_cost' => 'nullable|numeric|min:0',
            'next_service_date' => 'nullable|date',
            'next_service_mileage' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'technician_signature' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.type' => 'required_with:items|in:part,service',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.cost' => 'required_with:items|numeric|min:0',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.is_required' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $vehicle = Vehicle::find($data['vehicle_id']);

        $maintenance = Maintenance::create([
            'employee_id' => $employee->id,
            'vehicle_id' => $data['vehicle_id'],
            'contract_id' => $data['contract_id'] ?? null,
            'owner_id' => $vehicle->owner_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'diagnosed_issues' => $data['diagnosed_issues'] ?? null,
            'priority' => $data['priority'],
            'status' => 'submitted',
            'vehicle_mileage' => $data['vehicle_mileage'] ?? null,
            'estimated_cost' => $data['estimated_cost'] ?? 0,
            'next_service_date' => $data['next_service_date'] ?? null,
            'next_service_mileage' => $data['next_service_mileage'] ?? null,
            'notes' => $data['notes'] ?? null,
            'submitted_at' => now(),
            'technician_signature' => $data['technician_signature'] ?? null,
            'technician_signed_at' => !empty($data['technician_signature']) ? now() : null,
        ]);

        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $maintenance->items()->create([
                    'type' => $item['type'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'category' => $item['category'] ?? 'other',
                    'cost' => $item['cost'] ?? 0,
                    'quantity' => $item['quantity'] ?? 1,
                    'status' => 'pending',
                    'is_required' => $item['is_required'] ?? false,
                ]);
            }
        }

        $maintenance->load(['vehicle', 'contract', 'items']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance report created successfully',
            'data' => $maintenance->toApiResponse(),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        $maintenance = Maintenance::with(['vehicle', 'contract', 'items', 'employee'])
            ->where('employee_id', $employee->id)
            ->find($id);

        if (!$maintenance) {
            return response()->json(['success' => false, 'message' => 'Maintenance report not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $maintenance->toApiResponse(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        $maintenance = Maintenance::where('employee_id', $employee->id)->find($id);

        if (!$maintenance) {
            return response()->json(['success' => false, 'message' => 'Maintenance report not found'], 404);
        }

        if ($maintenance->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Cannot update a completed report'], 400);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'diagnosed_issues' => 'nullable|string',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'status' => 'sometimes|in:pending,submitted,in_progress,completed,cancelled',
            'vehicle_mileage' => 'nullable|numeric|min:0',
            'estimated_cost' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'next_service_date' => 'nullable|date',
            'next_service_mileage' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $maintenance->update($validator->validated());
        $maintenance->load(['vehicle', 'contract', 'items']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance report updated successfully',
            'data' => $maintenance->toApiResponse(),
        ]);
    }

    public function complete(Request $request, $id)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        $maintenance = Maintenance::where('employee_id', $employee->id)->find($id);

        if (!$maintenance) {
            return response()->json(['success' => false, 'message' => 'Maintenance report not found'], 404);
        }

        if ($maintenance->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Report is already completed'], 400);
        }

        $validator = Validator::make($request->all(), [
            'actual_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('actual_cost')) {
            $maintenance->actual_cost = $request->actual_cost;
        }
        if ($request->has('notes')) {
            $maintenance->notes = $request->notes;
        }

        $maintenance->complete();
        $maintenance->load(['vehicle', 'contract', 'items']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance report completed successfully',
            'data' => $maintenance->toApiResponse(),
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Technician profile not found'], 404);
        }

        $allReports = Maintenance::where('employee_id', $employee->id);

        $stats = [
            'total' => (clone $allReports)->count(),
            'pending' => (clone $allReports)->where('status', 'pending')->count(),
            'submitted' => (clone $allReports)->where('status', 'submitted')->count(),
            'processing' => (clone $allReports)->where('status', 'processing')->count(),
            'confirmed' => (clone $allReports)->where('status', 'confirmed')->count(),
            'verified' => (clone $allReports)->where('status', 'verified')->count(),
            'completed' => (clone $allReports)->where('status', 'completed')->count(),
            'cancelled' => (clone $allReports)->where('status', 'cancelled')->count(),
            'critical' => (clone $allReports)->where('priority', 'critical')->whereIn('status', ['submitted', 'viewed', 'processing'])->count(),
        ];

        $recentReports = Maintenance::with(['vehicle', 'items'])
            ->where('employee_id', $employee->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($m) => $m->toApiResponse());

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_reports' => $recentReports,
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'vehicle_id' => $employee->vehicle_id,
                    'vehicle_name' => $employee->vehicle?->name,
                ],
            ],
        ]);
    }

    public function updateItem(Request $request, $maintenanceId, $itemId)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        $maintenance = Maintenance::where('employee_id', $employee->id)->find($maintenanceId);

        if (!$maintenance) {
            return response()->json(['success' => false, 'message' => 'Maintenance report not found'], 404);
        }

        $item = MaintenanceItem::where('maintenance_id', $maintenanceId)->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,in_progress,completed,replaced',
            'cost' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:1',
            'is_required' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $item->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $item->toApiResponse(),
        ]);
    }

    public function addItem(Request $request, $maintenanceId)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        $maintenance = Maintenance::where('employee_id', $employee->id)->find($maintenanceId);

        if (!$maintenance) {
            return response()->json(['success' => false, 'message' => 'Maintenance report not found'], 404);
        }

        if ($maintenance->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Cannot add items to a completed report'], 400);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:part,service',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'cost' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'is_required' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $item = $maintenance->items()->create([
            'type' => $request->type,
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category ?? 'other',
            'cost' => $request->cost,
            'quantity' => $request->quantity ?? 1,
            'status' => 'pending',
            'is_required' => $request->is_required ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item added successfully',
            'data' => $item->toApiResponse(),
        ], 201);
    }

    public function destroyItem(Request $request, $maintenanceId, $itemId)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        $maintenance = Maintenance::where('employee_id', $employee->id)->find($maintenanceId);

        if (!$maintenance) {
            return response()->json(['success' => false, 'message' => 'Maintenance report not found'], 404);
        }

        $item = MaintenanceItem::where('maintenance_id', $maintenanceId)->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed successfully',
        ]);
    }

    public function vehicles(Request $request)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Technician profile not found'], 404);
        }

        $vehicleIds = Maintenance::where('employee_id', $employee->id)
            ->whereNotNull('vehicle_id')
            ->pluck('vehicle_id')
            ->unique();

        $vehicles = Vehicle::whereIn('id', $vehicleIds)
            ->orWhere('id', $employee->vehicle_id)
            ->get()
            ->unique('id');

        return response()->json([
            'success' => true,
            'data' => $vehicles,
        ]);
    }
}
