<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Owner;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user
     * POST /api/auth/register
     * Matches frontend: register(userData)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'role' => 'nullable|in:owner,customer',
            // Owner specific
            'business_name' => 'required_if:role,owner|string|max:255',
            'business_license' => 'required_if:role,owner|string|unique:owners,business_license',
            'business_address' => 'required_if:role,owner|string',
            'business_phone' => 'required_if:role,owner|string|max:20',
            // Employee specific
            'department' => 'required_if:role,employee|string',
            'position' => 'required_if:role,employee|string',
            'owner_id' => 'required_if:role,employee|exists:owners,id',
            // NIDA number
            'nida_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $role = $request->role ?? 'customer';

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone ?? $request->phone_number ?? $request->business_phone,
            'role' => $role,
            'nida_number' => $request->nida_number,
            'is_active' => true,
        ]);

        // Create role-specific records
        if ($role === 'owner') {
            Owner::create([
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'business_license' => $request->business_license,
                'business_address' => $request->business_address,
                'business_phone' => $request->business_phone,
                'business_email' => $request->email,
                'is_verified' => false,
            ]);
        } elseif ($role === 'employee') {
            Employee::create([
                'user_id' => $user->id,
                'owner_id' => $request->owner_id,
                'employee_id' => 'EMP-' . strtoupper(Str::random(8)),
                'department' => $request->department,
                'position' => $request->position,
                'hire_date' => now(),
                'status' => 'active',
            ]);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => $this->formatUserData($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    /**
     * Login user
     * POST /api/auth/login
     * Matches frontend: login(credentials)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Attempt login
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated'
            ], 403);
        }

        // Revoke old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Update last login
        $user->update(['last_login' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $this->formatUserData($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Logout user
     * POST /api/auth/logout
     * Matches frontend: logout()
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user
     * GET /api/auth/user
     * Matches frontend: fetchUser()
     */
    public function user(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUserData($user)
            ]
        ]);
    }

    /**
     * Refresh token
     * POST /api/auth/refresh
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // Revoke old token
        $request->user()->currentAccessToken()->delete();
        
        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Update profile
     * PUT /api/auth/profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'profile_image' => 'sometimes|string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'phone', 'profile_image']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $this->formatUserData($user)
            ]
        ]);
    }

    /**
     * Update NIDA number
     * PATCH /api/auth/update-nida
     * Matches frontend: updateNidaNumber(nidaNumber)
     */
    public function updateNida(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'nida_number' => 'required|string|max:20|unique:users,nida_number,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update(['nida_number' => $request->nida_number]);

        return response()->json([
            'success' => true,
            'message' => 'NIDA number updated successfully',
            'data' => [
                'user' => $this->formatUserData($user)
            ]
        ]);
    }

    /**
     * Change password
     * POST /api/auth/change-password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Update owner business profile
     * PUT /api/owner/profile
     */
    public function updateOwnerProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->isOwner()) {
            return response()->json(['success' => false, 'message' => 'Only owners can update business profile'], 403);
        }

        $owner = $user->owner;
        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'Owner profile not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'sometimes|string|max:255',
            'business_address' => 'sometimes|string',
            'business_phone' => 'sometimes|string|max:20',
            'business_email' => 'sometimes|email|max:255',
            'business_website' => 'nullable|string|max:255',
            'business_description' => 'nullable|string',
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update user fields
        $user->update($request->only(['name', 'phone']));

        // Update owner fields
        $owner->update($request->only([
            'business_name', 'business_address', 'business_phone',
            'business_email', 'business_website', 'business_description',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->fresh()->toApiResponse(),
            ],
        ]);
    }

    /**
     * Format user data to match frontend store structure
     */
    private function formatUserData(User $user)
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'can_drive' => $user->can_drive,
            'nida_number' => $user->nida_number,
            'profile_image' => $user->profile_image,
            'is_active' => $user->is_active,
            'last_login' => $user->last_login?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];

        // Add role-specific data
        $data['must_change_password'] = $user->mustChangePassword();

        if ($user->role === 'owner' && $user->owner) {
            $data['business'] = [
                'id' => $user->owner->id,
                'business_name' => $user->owner->business_name,
                'business_license' => $user->owner->business_license,
                'business_address' => $user->owner->business_address,
                'business_phone' => $user->owner->business_phone,
                'business_email' => $user->owner->business_email,
                'is_verified' => $user->owner->is_verified,
                'ai_enabled' => $user->owner->isAiEnabled(),
            ];
            $data['business_name'] = $user->owner->business_name;
            $data['is_verified'] = $user->owner->is_verified;
            $data['ai_enabled'] = $user->owner->isAiEnabled();
        } elseif (($user->role === 'employee' || $user->role === 'technician') && $user->employee) {
            $data['employee'] = [
                'id' => $user->employee->id,
                'employee_id' => $user->employee->employee_id,
                'department' => $user->employee->department,
                'position' => $user->employee->position,
                'hire_date' => $user->employee->hire_date?->toISOString(),
                'salary' => $user->employee->salary,
                'shift' => $user->employee->shift,
                'status' => $user->employee->status,
                'owner_id' => $user->employee->owner_id,
            ];
            $data['employee_id'] = $user->employee->employee_id;
            $data['department'] = $user->employee->department;
            $data['position'] = $user->employee->position;
            $data['owner_id'] = $user->employee->owner_id;
            // AI enabled based on owner's setting
            if ($user->employee->owner) {
                $data['ai_enabled'] = $user->employee->owner->isAiEnabled();
            }
        }

        return $data;
    }

    public function switchRole(Request $request)
    {
        $user = $request->user();

        if (!$user->can_drive) {
            return response()->json(['success' => false, 'message' => 'This account does not have dual role access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:employee,technician',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $newRole = $request->role;

        if ($newRole === $user->role) {
            return response()->json(['success' => true, 'message' => 'Already on ' . $newRole . ' dashboard', 'data' => ['user' => $this->formatUserData($user)]]);
        }

        $hasEmployeeRecord = Employee::where('user_id', $user->id)->where('position', $newRole === 'employee' ? 'Driver' : 'Technician')->exists();

        if (!$hasEmployeeRecord) {
            return response()->json(['success' => false, 'message' => 'No ' . $newRole . ' record found for this account'], 404);
        }

        $user->update(['role' => $newRole]);

        return response()->json([
            'success' => true,
            'message' => 'Switched to ' . $newRole . ' dashboard',
            'data' => ['user' => $this->formatUserData($user->fresh())],
        ]);
    }
}