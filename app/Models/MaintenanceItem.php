<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_id',
        'type',
        'name',
        'description',
        'category',
        'cost',
        'quantity',
        'status',
        'is_required',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'quantity' => 'integer',
        'is_required' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function maintenance()
    {
        return $this->belongsTo(Maintenance::class);
    }

    // ==================== ACCESSORS ====================

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'replaced' => 'Replaced',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => '#FFD93D',
            'in_progress' => '#00E5FF',
            'completed' => '#4ADE80',
            'replaced' => '#6C63FF',
        ];
        return $colors[$this->status] ?? 'rgba(255,255,255,0.3)';
    }

    public function getTypeLabelAttribute()
    {
        return $this->type === 'part' ? 'Part' : 'Service';
    }

    // ==================== API RESPONSE ====================

    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'maintenance_id' => $this->maintenance_id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'cost' => $this->cost,
            'quantity' => $this->quantity,
            'total_cost' => $this->cost * $this->quantity,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'is_required' => $this->is_required,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
