<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'registration',
        'year',
        'price',
        'status',
        'description',
        'image',
        'tags',
        'owner_id',
        'make',
        'model',
        'registration_number',
        'color',
        'mileage',
        'fuel_type',
        'transmission',
        'seats',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'insurance_required',
        'is_active',
        'next_service_date',
        'next_service_notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'next_service_date' => 'date',
        'price' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'mileage' => 'decimal:2',
        'seats' => 'integer',
        'insurance_required' => 'boolean',
        'is_active' => 'boolean',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function technician()
    {
        return $this->hasOne(Employee::class)->where('position', 'Technician');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'available' => 'Available',
            'on_contract' => 'On Contract',
            'maintenance' => 'Maintenance',
            'rented' => 'Rented',
            'assigned' => 'Assigned',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'available' => '#00E5FF',
            'on_contract' => '#FFD93D',
            'maintenance' => '#ff6b6b',
            'rented' => '#FFD93D',
            'assigned' => '#6C63FF',
        ];
        return $colors[$this->status] ?? 'rgba(255,255,255,0.3)';
    }

    public function getTypeIconAttribute()
    {
        $icons = [
            'Motorcycle' => 'fa-solid fa-motorcycle',
            'Bajaji' => 'fa-solid fa-truck-front',
            'Car' => 'fa-solid fa-car',
            'SUV' => 'fa-solid fa-truck',
        ];
        return $icons[$this->type] ?? 'fa-solid fa-car';
    }

    public static function getStats($ownerId = null)
    {
        $query = self::query();
        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }

        return [
            'total' => (clone $query)->count(),
            'available' => (clone $query)->where('status', 'available')->count(),
            'on_contract' => (clone $query)->where('status', 'on_contract')->count(),
            'maintenance' => (clone $query)->where('status', 'maintenance')->count(),
            'rented' => (clone $query)->where('status', 'rented')->count(),
            'assigned' => (clone $query)->where('status', 'assigned')->count(),
        ];
    }

    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'registration' => $this->registration,
            'year' => $this->year,
            'price' => $this->price,
            'status' => $this->status,
            'description' => $this->description,
            'image' => $this->image,
            'tags' => $this->tags,
            'owner_id' => $this->owner_id,
            'make' => $this->make,
            'model' => $this->model,
            'registration_number' => $this->registration_number,
            'daily_rate' => $this->daily_rate,
            'weekly_rate' => $this->weekly_rate,
            'monthly_rate' => $this->monthly_rate,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->format('Y-m-d'),
            'updated_at' => $this->updated_at?->format('Y-m-d'),
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'type_icon' => $this->type_icon,
        ];
    }
}
