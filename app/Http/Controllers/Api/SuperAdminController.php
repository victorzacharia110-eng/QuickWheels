<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Owner;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        // Only accessible by superadmin role
    }

    /**
     * Get superadmin dashboard overview
     */
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_owners' => Owner::count(),
                'verified_owners' => Owner::where('is_verified', true)->count(),
                'unverified_owners' => Owner::where('is_verified', false)->count(),
                'total_employees' => Employee::count(),
                'total_users' => User::count(),
                'total_vehicles' => \App\Models\Vehicle::count(),
                'recent_owners' => Owner::with('user')
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(fn($o) => $o->toApiResponse()),
            ],
        ]);
    }

    /**
     * List all owners
     */
    public function index(Request $request)
    {
        $query = Owner::with('user');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('business_name', 'like', "%{$request->search}%")
                  ->orWhere('business_license', 'like', "%{$request->search}%")
                  ->orWhere('business_email', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('verified')) {
            $query->where('is_verified', filter_var($request->verified, FILTER_VALIDATE_BOOLEAN));
        }

        $owners = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => collect($owners->items())->map(fn($o) => $o->toApiResponse()),
            'meta' => [
                'current_page' => $owners->currentPage(),
                'last_page' => $owners->lastPage(),
                'total' => $owners->total(),
                'per_page' => $owners->perPage(),
            ],
        ]);
    }

    /**
     * Show owner details
     */
    public function show($id)
    {
        $owner = Owner::with('user')->find($id);

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'Owner not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $owner->toApiResponse(),
        ]);
    }

    /**
     * Update owner business details
     */
    public function update(Request $request, $id)
    {
        $owner = Owner::find($id);

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'Owner not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'sometimes|string|max:255',
            'business_license' => 'sometimes|string|max:255|unique:owners,business_license,' . $id,
            'business_address' => 'sometimes|string',
            'business_phone' => 'sometimes|string|max:20',
            'business_email' => 'sometimes|email|max:255',
            'business_website' => 'nullable|string|max:255',
            'business_description' => 'nullable|string',
            'tax_id' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'tin_number' => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $owner->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Owner updated successfully',
            'data' => $owner->fresh()->toApiResponse(),
        ]);
    }

    /**
     * Toggle owner verification
     */
    public function toggleVerification($id)
    {
        $owner = Owner::find($id);

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'Owner not found'], 404);
        }

        if ($owner->is_verified) {
            $owner->unverify();
            $message = 'Owner unverified';
        } else {
            $owner->verify(request()->user()->id);
            $message = 'Owner verified successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $owner->fresh()->toApiResponse(),
        ]);
    }

    /**
     * Toggle owner user's active status
     */
    public function toggleUserStatus($id)
    {
        $owner = Owner::find($id);

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'Owner not found'], 404);
        }

        $user = $owner->user;
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Owner user not found'], 404);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'Owner activated' : 'Owner deactivated',
            'data' => $owner->fresh()->toApiResponse(),
        ]);
    }

    /**
     * Delete owner (soft delete)
     */
    public function destroy($id)
    {
        $owner = Owner::find($id);

        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'Owner not found'], 404);
        }

        $owner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Owner deleted successfully',
        ]);
    }

    /**
     * Get superadmin stats
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => Owner::getStats() + [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'admins' => User::where('role', 'superadmin')->count(),
                'owners_count' => User::where('role', 'owner')->count(),
                'employees_count' => User::where('role', 'employee')->count(),
                'customers_count' => User::where('role', 'customer')->count(),
            ],
        ]);
    }
}
