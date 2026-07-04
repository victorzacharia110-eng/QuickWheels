<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'nida_number',
        'license_number',
        'status',
        'joined_date',
        'vehicle_id',
        'vehicle_name',
        'owner_id',
        'user_id',
        'employee_id',
        'department',
        'position',
        'hire_date',
        'salary',
        'shift',
        'permissions',
        'supervisor_id',
        'profile_image',
    ];

    protected $casts = [
        'joined_date' => 'date',
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'permissions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user associated with the employee
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the owner of this employee
     */
    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Get the vehicle assigned to this employee
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the supervisor of this employee
     */
    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /**
     * Get subordinates of this employee
     */
    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    /**
     * Get bookings managed by this employee
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get contracts managed by this employee
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get maintenance records by this employee
     */
    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope for active employees
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive employees
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope for employees with assigned vehicles
     */
    public function scopeWithVehicle($query)
    {
        return $query->whereNotNull('vehicle_id');
    }

    /**
     * Scope for employees without assigned vehicles
     */
    public function scopeWithoutVehicle($query)
    {
        return $query->whereNull('vehicle_id');
    }

    /**
     * Scope for employees by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope for employees by position
     */
    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope for employees by name search
     */
    public function scopeSearchByName($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                     ->orWhere('email', 'like', "%{$search}%")
                     ->orWhere('phone', 'like', "%{$search}%")
                     ->orWhere('employee_id', 'like', "%{$search}%");
    }

    /**
     * Scope for employees with active status and vehicle
     */
    public function scopeActiveWithVehicle($query)
    {
        return $query->where('status', 'active')->whereNotNull('vehicle_id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the status label
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'on_leave' => 'On Leave',
            'terminated' => 'Terminated'
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get the status color (matches frontend)
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'active' => '#00E5FF',
            'inactive' => '#ff6b6b',
            'on_leave' => '#FFD93D',
            'terminated' => '#ff6b6b'
        ];
        return $colors[$this->status] ?? 'rgba(255,255,255,0.3)';
    }

    /**
     * Get the full name with title
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Get the employee display info
     */
    public function getDisplayInfoAttribute()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'vehicle_name' => $this->vehicle_name,
        ];
    }

    /**
     * Get the joined date formatted
     */
    public function getJoinedDateFormattedAttribute()
    {
        return $this->joined_date ? $this->joined_date->format('Y-m-d') : null;
    }

    /**
     * Get the hire date formatted
     */
    public function getHireDateFormattedAttribute()
    {
        return $this->hire_date ? $this->hire_date->format('Y-m-d') : null;
    }

    /**
     * Get the employee status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'active' => 'badge-success',
            'inactive' => 'badge-danger',
            'on_leave' => 'badge-warning',
            'terminated' => 'badge-dark'
        ];
        return $classes[$this->status] ?? 'badge-secondary';
    }

    /**
     * Check if employee has a vehicle assigned
     */
    public function getHasVehicleAttribute()
    {
        return !is_null($this->vehicle_id);
    }

    // ==================== MUTATORS ====================

    /**
     * Set the employee ID automatically if not provided
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (empty($employee->employee_id)) {
                $employee->employee_id = $employee->generateEmployeeId();
            }
            if (empty($employee->status)) {
                $employee->status = 'active';
            }
            if (empty($employee->joined_date) && empty($employee->hire_date)) {
                $employee->joined_date = now();
                $employee->hire_date = now();
            }
        });

        static::updating(function ($employee) {
            // If vehicle is removed, clear vehicle_name
            if ($employee->isDirty('vehicle_id') && is_null($employee->vehicle_id)) {
                $employee->vehicle_name = null;
            }
        });
    }

    // ==================== HELPER METHODS ====================

    /**
     * Generate a unique employee ID
     */
    public function generateEmployeeId()
    {
        $year = date('Y');
        $lastEmployee = self::where('employee_id', 'LIKE', 'EMP-' . $year . '-%')
            ->whereNotNull('employee_id')
            ->orderByRaw('LENGTH(employee_id) DESC, employee_id DESC')
            ->first();
        
        $number = $lastEmployee ? intval(substr($lastEmployee->employee_id, -4)) + 1 : 1;
        return 'EMP-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Assign a vehicle to this employee
     */
    public function assignVehicle($vehicleId)
    {
        $vehicle = Vehicle::find($vehicleId);
        
        if (!$vehicle) {
            throw new \Exception('Vehicle not found');
        }

        // Check if vehicle is already assigned to another employee
        $existing = self::where('vehicle_id', $vehicleId)
            ->where('status', 'active')
            ->first();
            
        if ($existing && $existing->id !== $this->id) {
            throw new \Exception('Vehicle already assigned to another active employee');
        }

        $this->vehicle_id = $vehicleId;
        $this->vehicle_name = $vehicle->name;
        $this->save();

        // Update vehicle status
        $vehicle->update(['status' => 'assigned']);

        return $this;
    }

    /**
     * Remove vehicle assignment from this employee
     */
    public function removeVehicle()
    {
        $vehicleId = $this->vehicle_id;
        
        $this->vehicle_id = null;
        $this->vehicle_name = null;
        $this->save();

        // Update vehicle status back to available
        if ($vehicleId) {
            $vehicle = Vehicle::find($vehicleId);
            if ($vehicle) {
                $vehicle->update(['status' => 'available']);
            }
        }

        return $this;
    }

    /**
     * Toggle employee status (active/inactive)
     */
    public function toggleStatus()
    {
        $this->status = $this->status === 'active' ? 'inactive' : 'active';
        
        // If deactivating, remove vehicle assignment
        if ($this->status === 'inactive' && $this->vehicle_id) {
            $this->removeVehicle();
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Check if employee is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if employee has a vehicle
     */
    public function hasVehicle()
    {
        return !is_null($this->vehicle_id);
    }

    /**
     * Get employee's vehicle details
     */
    public function getVehicleDetails()
    {
        if (!$this->hasVehicle()) {
            return null;
        }

        return [
            'id' => $this->vehicle_id,
            'name' => $this->vehicle_name,
        ];
    }

    /**
     * Get the employee's full details for API
     */
    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'nida_number' => $this->nida_number,
            'license_number' => $this->license_number,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'joined_date' => $this->joined_date_formatted,
            'vehicle_id' => $this->vehicle_id,
            'vehicle_name' => $this->vehicle_name,
            'has_vehicle' => $this->has_vehicle,
            'employee_id' => $this->employee_id,
            'department' => $this->department,
            'position' => $this->position,
            'hire_date' => $this->hire_date_formatted,
            'salary' => $this->salary,
            'shift' => $this->shift,
            'permissions' => $this->permissions,
            'supervisor_id' => $this->supervisor_id,
            'profile_image' => $this->profile_image,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get employee list for dropdown/select
     */
    public static function getSelectList($status = 'active')
    {
        return self::where('status', $status)
            ->orderBy('name')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'email' => $e->email,
                'phone' => $e->phone,
            ]);
    }

    /**
     * Get employees available for assignment (active and no vehicle)
     */
    public static function getAvailableForAssignment()
    {
        return self::where('status', 'active')
            ->whereNull('vehicle_id')
            ->orderBy('name')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'email' => $e->email,
                'phone' => $e->phone,
            ]);
    }

    /**
     * Get employees by department with stats
     */
    public static function getDepartmentStats()
    {
        return self::select('department', \DB::raw('count(*) as total'))
            ->groupBy('department')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->department => $item->total
            ]);
    }

    /**
     * Get employee statistics (matches frontend computed properties)
     */
    public static function getStats()
    {
        return [
            'total' => self::count(),
            'active' => self::where('status', 'active')->count(),
            'inactive' => self::where('status', 'inactive')->count(),
            'with_vehicles' => self::whereNotNull('vehicle_id')->count(),
            'without_vehicles' => self::whereNull('vehicle_id')->count(),
            'on_leave' => self::where('status', 'on_leave')->count(),
            'terminated' => self::where('status', 'terminated')->count(),
        ];
    }

    /**
     * Get employees with their assigned vehicles
     */
    public static function getWithVehicles()
    {
        return self::whereNotNull('vehicle_id')
            ->with('vehicle')
            ->get()
            ->map(fn($e) => $e->toApiResponse());
    }

    /**
     * Get employees without vehicles
     */
    public static function getWithoutVehicles()
    {
        return self::whereNull('vehicle_id')
            ->get()
            ->map(fn($e) => $e->toApiResponse());
    }
}