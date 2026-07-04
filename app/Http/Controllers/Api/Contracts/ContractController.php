<?php

namespace App\Http\Controllers\Api\Contracts;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    protected function applyOwnerScope(Request $request, $query)
    {
        if ($request->user()->role === 'owner') {
            $ownerId = $request->user()->owner?->id;
            if ($ownerId) {
                $query->where('owner_id', $ownerId);
            }
        } elseif ($request->user()->role === 'employee') {
            $query->where('employee_id', $request->user()->employee?->id);
        }
        return $query;
    }

    public function index(Request $request)
    {
        $query = Contract::with(['driver', 'vehicle', 'owner', 'employee']);
        $query = $this->applyOwnerScope($request, $query);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->driver_id) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->contract_type) {
            $query->where('contract_type', $request->contract_type);
        }

        if ($request->paginate === 'false') {
            $contracts = $query->latest()->get();
        } else {
            $contracts = $query->latest()->paginate($request->per_page ?? 15);
        }

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }
    

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'contract_type' => 'required|in:hire_purchase,rental',
            'payment_frequency' => 'required|in:daily,weekly,monthly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'total_amount' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
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
        $employee = \App\Models\Employee::find($data['employee_id']);
        $vehicle = \App\Models\Vehicle::find($data['vehicle_id']);
        $owner = $request->user()->owner;

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'Owner profile not found'], 400);
        }

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        if ($employee->user_id) {
            $data['driver_id'] = $employee->user_id;
        }
        $data['employee_id'] = $employee->id;
        $data['driver_name'] = $employee->name;
        $data['driver_phone'] = $employee->phone;
        $data['driver_email'] = $employee->email ?? null;
        $data['vehicle_name'] = $vehicle->name;
        $data['vehicle_type'] = $vehicle->type;
        $data['vehicle_registration'] = $vehicle->registration;
        $data['owner_id'] = $owner->id;
        $data['contract_number'] = 'CTR-' . strtoupper(uniqid());
        $data['status'] = 'pending';

        $contract = Contract::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Contract created successfully',
            'data' => $contract->load(['driver', 'vehicle', 'owner', 'employee']),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $contract = Contract::with(['driver', 'vehicle', 'owner', 'employee', 'payments'])->find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $contract,
        ]);
    }

    public function update(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'driver_id' => 'sometimes|exists:users,id',
            'driver_name' => 'sometimes|string|max:255',
            'driver_email' => 'nullable|email|max:255',
            'driver_phone' => 'sometimes|string|max:20',
            'vehicle_id' => 'sometimes|exists:vehicles,id',
            'vehicle_name' => 'sometimes|string|max:255',
            'vehicle_type' => 'sometimes|in:motorcycle,car,bajaj',
            'vehicle_registration' => 'sometimes|string|max:50',
            'contract_type' => 'sometimes|in:hire_purchase,rental',
            'payment_frequency' => 'sometimes|in:daily,weekly,monthly',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'weekly_amount' => 'nullable|numeric|min:0',
            'daily_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'sometimes|numeric|min:0',
            'paid_amount' => 'sometimes|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:active,pending,completed,expired,cancelled',
            'notes' => 'nullable|string',
            'owner_id' => 'sometimes|exists:owners,id',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $contract->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Contract updated successfully',
            'data' => $contract->fresh()->load(['driver', 'vehicle', 'owner', 'employee']),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contract deleted successfully',
        ]);
    }

    public function recordPayment(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string|max:50',
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
        $data['contract_id'] = $contract->id;
        $data['driver_id'] = $contract->driver_id;
        $data['driver_name'] = $contract->driver_name;
        $data['owner_id'] = $contract->owner_id;
        $data['status'] = 'paid';

        $payment = Payment::create($data);

        $contract->increment('paid_amount', $data['amount']);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'data' => $payment,
        ], 201);
    }

    public function signCustomer(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_signature' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $contract->update([
            'customer_signed_at' => now(),
            'customer_signature' => $request->customer_signature,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contract signed by customer',
            'data' => $contract->fresh(),
        ]);
    }

    public function signOwner(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $contract->update([
            'owner_signed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contract signed by owner',
            'data' => $contract->fresh(),
        ]);
    }

    public function send(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $contract->update(['status' => 'sent']);

        return response()->json([
            'success' => true,
            'message' => 'Contract sent successfully',
            'data' => $contract->fresh(),
        ]);
    }

    public function activate(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        if (!in_array($contract->status, ['pending', 'sent'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending/sent contracts can be activated',
            ], 400);
        }

        $contract->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contract activated successfully',
            'data' => $contract->fresh(),
        ]);
    }

    public function complete(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        if ($contract->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active contracts can be completed',
            ], 400);
        }

        $contract->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contract completed successfully',
            'data' => $contract->fresh(),
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $contract->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Contract cancelled successfully',
            'data' => $contract->fresh(),
        ]);
    }

    public function stats(Request $request)
    {
        $ownerId = $request->user()->owner?->id;

        $query = Contract::query();
        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }

        $contracts = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $contracts->count(),
                'active' => $contracts->where('status', 'active')->count(),
                'pending' => $contracts->where('status', 'pending')->count(),
                'completed' => $contracts->where('status', 'completed')->count(),
                'cancelled' => $contracts->where('status', 'cancelled')->count(),
                'expired' => $contracts->where('status', 'expired')->count(),
                'total_amount' => $contracts->sum('total_amount'),
                'paid_amount' => $contracts->sum('paid_amount'),
            ],
        ]);
    }

    public function dashboard(Request $request)
    {
        $ownerId = $request->user()->owner?->id;

        $query = Contract::where('owner_id', $ownerId);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => Contract::getStats($ownerId),
                'recent' => (clone $query)->latest()->limit(5)->get(),
                'expiring' => (clone $query)->where('status', 'active')
                    ->where('end_date', '<=', now()->addDays(7))
                    ->get(),
            ],
        ]);
    }

    public function getByDriver(Request $request, $driverId)
    {
        $contracts = Contract::where('driver_id', $driverId)
            ->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    public function getByDriverName(Request $request, $driverName)
    {
        $contracts = Contract::where('driver_name', 'like', "%{$driverName}%")
            ->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    public function download(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $contract->load(['driver', 'vehicle', 'owner', 'employee', 'payments']);

        $pdfData = [
            'contract' => $contract,
            'generated_at' => now()->toDateTimeString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Contract download prepared',
            'data' => $pdfData,
        ]);
    }
}
