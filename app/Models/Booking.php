<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_number',
        'customer_id',
        'vehicle_id',
        'owner_id',
        'employee_id',
        'assigned_driver_id',
        'start_date',
        'end_date',
        'pickup_time',
        'return_time',
        'scheduled_at',
        'pickup_location',
        'destination',
        'return_location',
        'delivery_address',
        'fare',
        'total_amount',
        'deposit_paid',
        'balance',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'discount',
        'discount_code',
        'late_fee',
        'cleaning_fee',
        'insurance_fee',
        'status',
        'notes',
        'cancellation_reason',
        'cancelled_at',
        'completed_at',
        'accepted_at',
        'en_route_at',
        'started_at',
        'driver_name',
        'driver_license',
        'driver_age',
        'driver_phone',
        'is_driver_required',
        'is_delivery_required',
        'is_insurance_included',
        'is_contract_signed',
        'payment_method',
        'payment_status',
        'transaction_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pickup_time' => 'datetime',
        'return_time' => 'datetime',
        'scheduled_at' => 'datetime',
        'accepted_at' => 'datetime',
        'en_route_at' => 'datetime',
        'started_at' => 'datetime',
        'fare' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'deposit_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'insurance_fee' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_driver_required' => 'boolean',
        'is_delivery_required' => 'boolean',
        'is_insurance_included' => 'boolean',
        'is_contract_signed' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the customer (user) who made the booking.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the vehicle being booked.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the owner of the vehicle.
     */
    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Get the employee who handled this booking.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the assigned driver (employee) for this ride.
     */
    public function assignedDriver()
    {
        return $this->belongsTo(Employee::class, 'assigned_driver_id');
    }

    /**
     * Get the payment for this booking.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get the contract for this booking.
     */
    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    /**
     * Get the review for this booking.
     */
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope for pending bookings.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope for active bookings.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed bookings.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled bookings.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for bookings between dates.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate]);
        });
    }

    /**
     * Scope for bookings by customer.
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for bookings by vehicle.
     */
    public function scopeByVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope for bookings by owner.
     */
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * Scope for bookings by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for today's bookings.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start_date', today())
                     ->orWhereDate('end_date', today());
    }

    /**
     * Scope for this month's bookings.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('start_date', now()->month)
                     ->whereYear('start_date', now()->year);
    }

    /**
     * Scope for bookings ending soon (within 7 days).
     */
    public function scopeEndingSoon($query)
    {
        return $query->where('end_date', '<=', now()->addDays(7))
                     ->where('status', 'active');
    }

    /**
     * Scope for bookings needing attention (pending/confirmation).
     */
    public function scopeNeedsAttention($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'requested' => 'Requested',
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'confirmed' => 'Confirmed',
            'en_route' => 'Driver En Route',
            'active' => 'Active',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get the status color.
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'requested' => '#FFD93D',
            'pending' => '#FFD93D',
            'accepted' => '#22d3ee',
            'confirmed' => '#4ADE80',
            'en_route' => '#6366f1',
            'active' => '#00E5FF',
            'in_progress' => '#00E5FF',
            'completed' => '#4ade80',
            'cancelled' => '#ff6b6b',
        ];
        return $colors[$this->status] ?? 'rgba(255,255,255,0.3)';
    }

    /**
     * Get formatted total amount.
     */
    public function getTotalAmountFormattedAttribute()
    {
        return 'TSh ' . number_format($this->total_amount, 0);
    }

    /**
     * Get formatted deposit paid.
     */
    public function getDepositPaidFormattedAttribute()
    {
        return 'TSh ' . number_format($this->deposit_paid, 0);
    }

    /**
     * Get formatted balance.
     */
    public function getBalanceFormattedAttribute()
    {
        return 'TSh ' . number_format($this->balance, 0);
    }

    /**
     * Get the booking duration in days.
     */
    public function getDurationAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Get the booking duration in words.
     */
    public function getDurationLabelAttribute()
    {
        $days = $this->duration;
        if ($days <= 0) return '0 days';
        if ($days == 1) return '1 day';
        return $days . ' days';
    }

    /**
     * Get days remaining until end.
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->end_date || $this->status === 'completed' || $this->status === 'cancelled') {
            return 0;
        }
        $diff = now()->diffInDays($this->end_date, false);
        return $diff > 0 ? $diff : 0;
    }

    /**
     * Check if booking is overdue.
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->end_date || $this->status === 'completed' || $this->status === 'cancelled') {
            return false;
        }
        return now()->gt($this->end_date);
    }

    /**
     * Get payment status label.
     */
    public function getPaymentStatusLabelAttribute()
    {
        $labels = [
            'paid' => 'Paid',
            'partial' => 'Partial',
            'unpaid' => 'Unpaid',
            'refunded' => 'Refunded',
        ];
        return $labels[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * Check if booking is fully paid.
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->balance <= 0;
    }

    // ==================== MUTATORS ====================

    /**
     * Set default values on creation.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = $booking->generateBookingNumber();
            }
            if (empty($booking->status)) {
                $booking->status = 'requested';
            }
            if (empty($booking->start_date)) {
                $booking->start_date = $booking->scheduled_at?->toDateString() ?? today();
            }
            if (empty($booking->balance)) {
                $booking->balance = $booking->total_amount - ($booking->deposit_paid ?? 0);
            }
        });
    }

    // ==================== HELPER METHODS ====================

    /**
     * Generate a unique booking number.
     */
    public function generateBookingNumber()
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        
        // Format: BK-2024-06-15-001
        $lastBooking = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastBooking ? intval(substr($lastBooking->booking_number, -3)) + 1 : 1;
        
        return 'BK-' . $year . '-' . $month . '-' . $day . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total amount based on dates and rates.
     */
    public function calculateTotal()
    {
        $days = $this->duration;
        $total = $days * $this->daily_rate;
        
        // Apply discount if any
        if ($this->discount) {
            $total -= $this->discount;
        }
        
        // Add fees
        if ($this->insurance_fee) {
            $total += $this->insurance_fee;
        }
        if ($this->cleaning_fee) {
            $total += $this->cleaning_fee;
        }
        
        $this->total_amount = $total;
        $this->balance = $total - $this->deposit_paid;
        
        return $this;
    }

    /**
     * Accept a ride request (driver accepts).
     */
    public function accept($driverId = null)
    {
        if (!in_array($this->status, ['requested', 'pending'])) {
            throw new \Exception('Only requested bookings can be accepted');
        }

        $this->status = 'accepted';
        $this->accepted_at = now();
        if ($driverId) {
            $this->assigned_driver_id = $driverId;
        }
        $this->save();

        return $this;
    }

    /**
     * Mark driver as en route to pickup.
     */
    public function markEnRoute()
    {
        if ($this->status !== 'accepted') {
            throw new \Exception('Only accepted bookings can be marked en route');
        }

        $this->status = 'en_route';
        $this->en_route_at = now();
        $this->save();

        return $this;
    }

    /**
     * Start the ride (picked up passenger).
     */
    public function startRide()
    {
        if ($this->status !== 'en_route') {
            throw new \Exception('Only en_route bookings can be started');
        }

        $this->status = 'in_progress';
        $this->started_at = now();
        $this->save();

        return $this;
    }

    /**
     * Complete the ride.
     */
    public function completeRide($fare = null)
    {
        if (!in_array($this->status, ['in_progress', 'en_route', 'active'])) {
            throw new \Exception('Only in-progress rides can be completed');
        }

        $this->status = 'completed';
        $this->completed_at = now();
        if ($fare !== null) {
            $this->fare = $fare;
            $this->total_amount = $fare;
        }
        $this->save();

        return $this;
    }

    /**
     * Approve/confirm the booking (legacy).
     */
    public function confirm($employeeId = null)
    {
        return $this->accept($employeeId);
    }

    /**
     * Start the booking (legacy).
     */
    public function start()
    {
        return $this->startRide();
    }

    /**
     * Complete the booking (legacy).
     */
    public function complete()
    {
        return $this->completeRide();
    }

    /**
     * Cancel the booking.
     */
    public function cancel($reason = null)
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            throw new \Exception('Completed/cancelled bookings cannot be cancelled');
        }

        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->save();

        return $this;
    }

    /**
     * Record a payment for this booking.
     */
    public function recordPayment($amount, $method = 'cash')
    {
        $this->deposit_paid += $amount;
        $this->balance = $this->total_amount - $this->deposit_paid;
        
        if ($this->balance <= 0) {
            $this->payment_status = 'paid';
            $this->balance = 0;
        } else {
            $this->payment_status = 'partial';
        }
        
        $this->payment_method = $method;
        $this->save();

        // Create payment record
        return Payment::create([
            'booking_id' => $this->id,
            'amount' => $amount,
            'method' => $method,
            'status' => 'completed',
            'date' => now(),
            'driver_name' => $this->customer->name ?? null,
            'driver_id' => $this->customer_id,
        ]);
    }

    /**
     * Check if booking is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if booking is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if booking is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get booking statistics for dashboard.
     */
    public static function getStats($ownerId = null)
    {
        $query = self::query();
        
        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'total_revenue' => (clone $query)->where('status', 'completed')->sum('total_amount'),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            'today' => (clone $query)->whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Get booking by booking number.
     */
    public static function findByBookingNumber($bookingNumber)
    {
        return self::where('booking_number', $bookingNumber)->first();
    }

    /**
     * Check if vehicle is available for dates.
     */
    public static function isVehicleAvailable($vehicleId, $startDate, $endDate)
    {
        return !self::where('vehicle_id', $vehicleId)
            ->whereIn('status', ['pending', 'confirmed', 'active'])
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            })
            ->exists();
    }

    /**
     * Format booking data for API response.
     */
    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->name,
            'customer_phone' => $this->customer?->phone,
            'vehicle_id' => $this->vehicle_id,
            'vehicle_name' => $this->vehicle?->name ?? null,
            'vehicle_type' => $this->vehicle?->type,
            'owner_id' => $this->owner_id,
            'owner_name' => $this->owner?->business_name,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee?->name,
            'assigned_driver_id' => $this->assigned_driver_id,
            'assigned_driver_name' => $this->assignedDriver?->name,
            'assigned_driver_phone' => $this->assignedDriver?->phone,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'pickup_time' => $this->pickup_time?->toTimeString(),
            'scheduled_at' => $this->scheduled_at?->toDateTimeString(),
            'return_time' => $this->return_time?->toTimeString(),
            'duration' => $this->duration,
            'duration_label' => $this->duration_label,
            'days_remaining' => $this->days_remaining,
            'is_overdue' => $this->is_overdue,
            'pickup_location' => $this->pickup_location,
            'destination' => $this->destination,
            'return_location' => $this->return_location,
            'fare' => $this->fare,
            'total_amount' => $this->total_amount,
            'total_amount_formatted' => $this->total_amount_formatted,
            'deposit_paid' => $this->deposit_paid,
            'deposit_paid_formatted' => $this->deposit_paid_formatted,
            'balance' => $this->balance,
            'balance_formatted' => $this->balance_formatted,
            'is_fully_paid' => $this->is_fully_paid,
            'discount' => $this->discount,
            'discount_code' => $this->discount_code,
            'late_fee' => $this->late_fee,
            'cleaning_fee' => $this->cleaning_fee,
            'insurance_fee' => $this->insurance_fee,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'accepted_at' => $this->accepted_at?->toDateTimeString(),
            'en_route_at' => $this->en_route_at?->toDateTimeString(),
            'started_at' => $this->started_at?->toDateTimeString(),
            'driver_name' => $this->driver_name,
            'driver_license' => $this->driver_license,
            'driver_age' => $this->driver_age,
            'driver_phone' => $this->driver_phone,
            'is_driver_required' => $this->is_driver_required,
            'is_delivery_required' => $this->is_delivery_required,
            'is_insurance_included' => $this->is_insurance_included,
            'is_contract_signed' => $this->is_contract_signed,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}