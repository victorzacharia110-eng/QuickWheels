<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'email',
    'password',
    'phone',
    'role',
    'nida_number',
    'profile_image',
    'is_active',
    'last_login'
])]
#[Hidden([
    'password',
    'remember_token'
])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the owner record associated with the user.
     */
    public function owner()
    {
        return $this->hasOne(Owner::class);
    }

    /**
     * Get the employee record associated with the user.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the bookings made by this user (as customer).
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    /**
     * Get the payments made by this user.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the contracts for this user (as driver).
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'driver_id');
    }



    // ==================== ROLE CHECKS ====================

    /**
     * Check if user is an owner.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is an employee.
     */
    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    /**
     * Check if user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Check if user is a superadmin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Check if user is a technician.
     */
    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if user is verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to only include users by role.
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope a query to only include owners.
     */
    public function scopeOwners($query)
    {
        return $query->where('role', 'owner');
    }

    /**
     * Scope a query to only include employees.
     */
    public function scopeEmployees($query)
    {
        return $query->where('role', 'employee');
    }

    /**
     * Scope a query to only include customers.
     */
    public function scopeCustomers($query)
    {
        return $query->where('role', 'customer');
    }

    /**
     * Scope a query to search by name or email.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                     ->orWhere('email', 'like', "%{$search}%")
                     ->orWhere('phone', 'like', "%{$search}%");
    }

    /**
     * Scope a query to find by NIDA number.
     */
    public function scopeByNida($query, $nida)
    {
        return $query->where('nida_number', $nida);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the user's full name (alias for name).
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Get the user's role label.
     */
    public function getRoleLabelAttribute()
    {
        $labels = [
            'owner' => 'Owner',
            'employee' => 'Employee',
            'customer' => 'Customer',
            'technician' => 'Technician',
        ];
        return $labels[$this->role] ?? $this->role;
    }

    /**
     * Get the user's status label.
     */
    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Get the user's status color.
     */
    public function getStatusColorAttribute()
    {
        return $this->is_active ? '#00E5FF' : '#ff6b6b';
    }

    /**
     * Get the user's initials.
     */
    public function getInitialsAttribute()
    {
        $words = explode(' ', $this->name);
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return $initials;
    }

    /**
     * Get the user's display name (with role).
     */
    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->role_label . ')';
    }

    /**
     * Get formatted phone number.
     */
    public function getPhoneFormattedAttribute()
    {
        if (!$this->phone) return null;
        // Format: +255 712 345 678
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($phone) === 12 && substr($phone, 0, 2) === '255') {
            return '+255 ' . substr($phone, 2, 3) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8, 3);
        }
        return $this->phone;
    }

    /**
     * Get the user's business name (if owner).
     */
    public function getBusinessNameAttribute()
    {
        if ($this->isOwner() && $this->owner) {
            return $this->owner->business_name;
        }
        return null;
    }

    /**
     * Get the user's employee ID (if employee).
     */
    public function getEmployeeIdAttribute()
    {
        if ($this->isEmployee() && $this->employee) {
            return $this->employee->employee_id;
        }
        return null;
    }

    // ==================== HELPERS ====================

    /**
     * Get the user's role-specific data.
     */
    public function getRoleData()
    {
        if ($this->isOwner()) {
            return $this->owner;
        } elseif ($this->isEmployee() || $this->isTechnician()) {
            return $this->employee;
        }
        return null;
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission($permission)
    {
        // Owner has all permissions
        if ($this->isOwner()) {
            return true;
        }

        // Technician has maintenance permissions
        if ($this->isTechnician()) {
            $techPermissions = ['view_vehicles', 'create_maintenance', 'edit_maintenance', 'view_maintenance'];
            return in_array($permission, $techPermissions);
        }

        // Check employee permissions
        if ($this->isEmployee() && $this->employee && $this->employee->permissions) {
            return in_array($permission, $this->employee->permissions);
        }

        // Customer permissions
        if ($this->isCustomer()) {
            $customerPermissions = ['view_vehicles', 'create_booking', 'view_own_bookings'];
            return in_array($permission, $customerPermissions);
        }

        return false;
    }

    /**
     * Get user statistics.
     */
    public function getStats()
    {
        return [
            'total_bookings' => $this->bookings()->count(),
            'total_payments' => $this->payments()->count(),
            'total_contracts' => $this->contracts()->count(),
            'active_bookings' => $this->bookings()->whereIn('status', ['pending', 'confirmed', 'active'])->count(),
            'total_spent' => $this->payments()->sum('amount'),
            'member_since' => $this->created_at?->format('Y-m-d'),
        ];
    }

    /**
     * Format user data for API response (matches frontend).
     */
    public function toApiResponse()
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'nida_number' => $this->nida_number,
            'profile_image' => $this->profile_image,
            'is_active' => $this->is_active,
            'last_login' => $this->last_login?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];

        // Add role-specific data
        if ($this->isOwner() && $this->owner) {
            $data['business'] = [
                'id' => $this->owner->id,
                'business_name' => $this->owner->business_name,
                'business_license' => $this->owner->business_license,
                'business_address' => $this->owner->business_address,
                'business_phone' => $this->owner->business_phone,
                'business_email' => $this->owner->business_email,
                'is_verified' => $this->owner->is_verified,
                'total_vehicles' => $this->owner->total_vehicles,
                'total_earnings' => $this->owner->total_earnings,
            ];
            $data['business_name'] = $this->owner->business_name;
            $data['is_verified'] = $this->owner->is_verified;
        } elseif ($this->isEmployee() && $this->employee) {
            $data['employee'] = [
                'id' => $this->employee->id,
                'employee_id' => $this->employee->employee_id,
                'department' => $this->employee->department,
                'position' => $this->employee->position,
                'hire_date' => $this->employee->hire_date?->toDateTimeString(),
                'salary' => $this->employee->salary,
                'shift' => $this->employee->shift,
                'status' => $this->employee->status,
                'owner_id' => $this->employee->owner_id,
                'supervisor_id' => $this->employee->supervisor_id,
            ];
            $data['employee_id'] = $this->employee->employee_id;
            $data['department'] = $this->employee->department;
            $data['position'] = $this->employee->position;
            $data['owner_id'] = $this->employee->owner_id;
        }

        return $data;
    }

    /**
     * Get user for dropdown/select.
     */
    public static function getSelectList($role = null)
    {
        $query = self::query();
        
        if ($role) {
            $query->where('role', $role);
        }
        
        return $query->orderBy('name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]);
    }

    /**
     * Get active users with role.
     */
    public static function getActiveByRole($role)
    {
        return self::where('role', $role)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get user by NIDA number.
     */
    public static function findByNida($nida)
    {
        return self::where('nida_number', $nida)->first();
    }

    /**
     * Get user by email.
     */
    public static function findByEmail($email)
    {
        return self::where('email', $email)->first();
    }
}