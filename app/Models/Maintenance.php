<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'report_number',
        'employee_id',
        'vehicle_id',
        'contract_id',
        'owner_id',
        'title',
        'description',
        'diagnosed_issues',
        'priority',
        'status',
        'vehicle_mileage',
        'estimated_cost',
        'actual_cost',
        'next_service_date',
        'next_service_mileage',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'vehicle_mileage' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'next_service_date' => 'date',
        'next_service_mileage' => 'decimal:2',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function items()
    {
        return $this->hasMany(MaintenanceItem::class);
    }

    public function parts()
    {
        return $this->hasMany(MaintenanceItem::class)->where('type', 'part');
    }

    public function services()
    {
        return $this->hasMany(MaintenanceItem::class)->where('type', 'service');
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // ==================== ACCESSORS ====================

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => '#FFD93D',
            'in_progress' => '#00E5FF',
            'completed' => '#4ADE80',
            'cancelled' => '#ff6b6b',
        ];
        return $colors[$this->status] ?? 'rgba(255,255,255,0.3)';
    }

    public function getPriorityLabelAttribute()
    {
        $labels = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
        return $labels[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => '#4ADE80',
            'medium' => '#FFD93D',
            'high' => '#fb923c',
            'critical' => '#ff6b6b',
        ];
        return $colors[$this->priority] ?? 'rgba(255,255,255,0.3)';
    }

    // ==================== MUTATORS ====================

    public static function boot()
    {
        parent::boot();

        static::creating(function ($maintenance) {
            if (empty($maintenance->report_number)) {
                $maintenance->report_number = $maintenance->generateReportNumber();
            }
        });
    }

    // ==================== HELPER METHODS ====================

    public function generateReportNumber()
    {
        $year = date('Y');
        $lastReport = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastReport ? intval(substr($lastReport->report_number, -3)) + 1 : 1;
        return 'MT-' . $year . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function complete()
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->items()->where('status', '!=', 'completed')->update(['status' => 'completed']);
        $this->save();
        return $this;
    }

    public function getItemsTotal()
    {
        return $this->items->sum(fn($item) => $item->cost * $item->quantity);
    }

    public function getRequiredPartsCount()
    {
        return $this->parts()->where('is_required', true)->count();
    }

    public function getCompletedItemsCount()
    {
        return $this->items()->where('status', 'completed')->count();
    }

    public function getProgressAttribute()
    {
        $total = $this->items->count();
        if ($total === 0) return 0;
        return round(($this->getCompletedItemsCount() / $total) * 100);
    }

    // ==================== API RESPONSE ====================

    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'report_number' => $this->report_number,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee?->name,
            'vehicle_id' => $this->vehicle_id,
            'vehicle_name' => $this->vehicle?->name,
            'vehicle_type' => $this->vehicle?->type,
            'vehicle_registration' => $this->vehicle?->registration_number,
            'vehicle_color' => $this->vehicle?->color,
            'vehicle_chassis_number' => $this->vehicle?->chassis_number,
            'contract_id' => $this->contract_id,
            'contract_number' => $this->contract?->contract_number,
            'owner_id' => $this->owner_id,
            'title' => $this->title,
            'description' => $this->description,
            'diagnosed_issues' => $this->diagnosed_issues,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'priority_color' => $this->priority_color,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'vehicle_mileage' => $this->vehicle_mileage,
            'estimated_cost' => $this->estimated_cost,
            'actual_cost' => $this->actual_cost,
            'next_service_date' => $this->next_service_date?->format('Y-m-d'),
            'next_service_mileage' => $this->next_service_mileage,
            'notes' => $this->notes,
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'progress' => $this->progress,
            'items' => $this->items->map(fn($item) => $item->toApiResponse()),
            'parts_count' => $this->parts->count(),
            'services_count' => $this->services->count(),
            'required_parts_count' => $this->getRequiredPartsCount(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
