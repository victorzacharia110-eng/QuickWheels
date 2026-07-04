<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * List all customers (for employee view)
     */
    public function index(Request $request)
    {
        $customers = User::where('role', 'customer')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'nida_number' => $user->nida_number,
                'profile_image' => $user->profile_image,
                'is_active' => $user->is_active,
                'last_login' => $user->last_login?->toDateTimeString(),
                'created_at' => $user->created_at?->toDateTimeString(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * Show customer details
     */
    public function show(Request $request, $id)
    {
        $user = User::where('role', 'customer')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'nida_number' => $user->nida_number,
                'profile_image' => $user->profile_image,
                'is_active' => $user->is_active,
                'last_login' => $user->last_login?->toDateTimeString(),
                'role' => $user->role,
                'created_at' => $user->created_at?->toDateTimeString(),
                'updated_at' => $user->updated_at?->toDateTimeString(),
            ],
        ]);
    }
}
