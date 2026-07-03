<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_number',
        'driver_id',
        'driver_name',
        'driver_email',
        'driver_phone',
        'vehicle_id',
        'vehicle_name',
        'vehicle_type',
        'vehicle_registration',
        'contract_type',
        'payment_frequency',
        'start_date',
        'end_date',
        'weekly_amount',
        'daily_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'deposit',
        'status',
        'notes',
        'owner_id',        
        'employee_id',     
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'weekly_amount' => 'decimal:2',
        'daily_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'deposit' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================
    
    /**
     * Get the driver (user) associated with the contract
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Get the vehicle associated with the contract
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the owner associated with the contract
     */
    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Get the employee who created/manages the contract
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get payments for this contract
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope for active contracts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for pending contracts
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed contracts
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled contracts
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for expired contracts
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for hire purchase contracts
     */
    public function scopeHirePurchase($query)
    {
        return $query->where('contract_type', 'hire_purchase');
    }

    /**
     * Scope for rental contracts
     */
    public function scopeRental($query)
    {
        return $query->where('contract_type', 'rental');
    }

    /**
     * Scope for contracts by owner
     */
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * Scope for contracts by driver
     */
    public function scopeByDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Scope for contracts by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for contracts with remaining balance
     */
    public function scopeWithBalance($query)
    {
        return $query->where('remaining_amount', '>', 0);
    }

    /**
     * Scope for contracts ending soon (within 7 days)
     */
    public function scopeEndingSoon($query)
    {
        return $query->where('end_date', '<=', now()->addDays(7))
                    ->where('status', 'active');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the status label
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'active' => 'Active',
            'pending' => 'Pending',
            'completed' => 'Completed',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled'
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get the status color
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'active' => '#00E5FF',
            'pending' => '#FFD93D',
            'completed' => '#4ADE80',
            'expired' => '#ff6b6b',
            'cancelled' => '#ff6b6b'
        ];
        return $colors[$this->status] ?? 'rgba(255,255,255,0.3)';
    }

    /**
     * Get the contract type label
     */
    public function getContractTypeLabelAttribute()
    {
        $labels = [
            'hire_purchase' => 'Hire Purchase',
            'rental' => 'Rental'
        ];
        return $labels[$this->contract_type] ?? $this->contract_type;
    }

    /**
     * Get the payment frequency label
     */
    public function getPaymentFrequencyLabelAttribute()
    {
        $labels = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly'
        ];
        return $labels[$this->payment_frequency] ?? $this->payment_frequency;
    }

    /**
     * Get progress percentage
     */
    public function getProgressAttribute()
    {
        if (!$this->total_amount || $this->total_amount == 0) {
            return 0;
        }
        return round(($this->paid_amount / $this->total_amount) * 100);
    }

    /**
     * Get days remaining
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->end_date) {
            return null;
        }
        $end = new \DateTime($this->end_date);
        $now = new \DateTime();
        $diff = $end->diff($now);
        return $diff->days;
    }

    /**
     * Check if contract is fully paid
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->remaining_amount <= 0;
    }

    // ==================== MUTATORS ====================

    /**
     * Set the contract number automatically if not provided
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $contract->contract_number = $contract->generateContractNumber();
            }
            if (empty($contract->remaining_amount)) {
                $contract->remaining_amount = $contract->total_amount - $contract->paid_amount;
            }
        });

        static::updating(function ($contract) {
            // Update remaining amount when paid amount changes
            if ($contract->isDirty('paid_amount')) {
                $contract->remaining_amount = $contract->total_amount - $contract->paid_amount;
                
                // Auto-complete if fully paid
                if ($contract->remaining_amount <= 0 && $contract->status === 'active') {
                    $contract->status = 'completed';
                    $contract->remaining_amount = 0;
                }
            }
        });
    }

    // ==================== HELPER METHODS ====================

    /**
     * Generate a unique contract number
     */
    public function generateContractNumber()
    {
        $year = date('Y');
        $lastContract = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastContract ? intval(substr($lastContract->contract_number, -3)) + 1 : 1;
        return 'CT-' . $year . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Record a payment for this contract
     */
    public function recordPayment($amount, $paymentMethod = 'cash')
    {
        $this->paid_amount += $amount;
        $this->remaining_amount = $this->total_amount - $this->paid_amount;
        
        if ($this->remaining_amount <= 0) {
            $this->status = 'completed';
            $this->remaining_amount = 0;
        }
        
        $this->save();
        
        // Create payment record
        return $this->payments()->create([
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => 'completed',
            'transaction_id' => 'TXN-' . date('Ymd') . '-' . strtoupper(uniqid()),
            'paid_at' => now(),
            'contract_id' => $this->id,
            'driver_id' => $this->driver_id,
            'driver_name' => $this->driver_name,
            'owner_id' => $this->owner_id,
        ]);
    }

    /**
     * Check if contract is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if contract is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if contract is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if contract is expired
     */
    public function isExpired()
    {
        return $this->status === 'expired' || 
               ($this->end_date && now()->gt($this->end_date) && $this->status !== 'completed');
    }

    /**
     * Check if contract is hire purchase
     */
    public function isHirePurchase()
    {
        return $this->contract_type === 'hire_purchase';
    }

    /**
     * Check if contract is rental
     */
    public function isRental()
    {
        return $this->contract_type === 'rental';
    }

    /**
     * Get formatted amount with currency
     */
    public function formatAmount($amount)
    {
        return 'TSh ' . number_format($amount, 0);
    }

    /**
     * Get total amount formatted
     */
    public function getTotalAmountFormattedAttribute()
    {
        return $this->formatAmount($this->total_amount);
    }

    /**
     * Get paid amount formatted
     */
    public function getPaidAmountFormattedAttribute()
    {
        return $this->formatAmount($this->paid_amount);
    }

    /**
     * Get remaining amount formatted
     */
    public function getRemainingAmountFormattedAttribute()
    {
        return $this->formatAmount($this->remaining_amount);
    }

    /**
     * Get deposit formatted
     */
    public function getDepositFormattedAttribute()
    {
        return $this->formatAmount($this->deposit);
    }

    /**
     * Get weekly amount formatted
     */
    public function getWeeklyAmountFormattedAttribute()
    {
        return $this->formatAmount($this->weekly_amount);
    }

    /**
     * Get daily amount formatted
     */
    public function getDailyAmountFormattedAttribute()
    {
        return $this->formatAmount($this->daily_amount);
    }

    // ==================== STATISTICS ====================

    /**
     * Get contract statistics
     */
    public static function getStats($ownerId = null)
    {
        $query = self::query();
        
        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'expired' => (clone $query)->where('status', 'expired')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'hire_purchase' => (clone $query)->where('contract_type', 'hire_purchase')->count(),
            'rental' => (clone $query)->where('contract_type', 'rental')->count(),
            'total_amount' => (clone $query)->sum('total_amount'),
            'total_paid' => (clone $query)->sum('paid_amount'),
            'total_remaining' => (clone $query)->sum('remaining_amount'),
        ];
    }

    /**
     * Get contracts by owner with totals
     */
    public static function getByOwnerWithTotals($ownerId)
    {
        $contracts = self::where('owner_id', $ownerId)->get();
        
        return [
            'contracts' => $contracts->map(fn($c) => $c->toApiResponse()),
            'total_contracts' => $contracts->count(),
            'total_amount' => $contracts->sum('total_amount'),
            'total_paid' => $contracts->sum('paid_amount'),
            'total_remaining' => $contracts->sum('remaining_amount'),
            'active_contracts' => $contracts->where('status', 'active')->count(),
            'active_amount' => $contracts->where('status', 'active')->sum('remaining_amount'),
        ];
    }

    // ==================== API RESPONSE ====================

    /**
     * Convert to API response format (matches frontend store)
     */
    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'contract_number' => $this->contract_number,
            'driver_id' => $this->driver_id,
            'driver_name' => $this->driver_name,
            'driver_email' => $this->driver_email,
            'driver_phone' => $this->driver_phone,
            'vehicle_id' => $this->vehicle_id,
            'vehicle_name' => $this->vehicle_name,
            'vehicle_type' => $this->vehicle_type,
            'vehicle_registration' => $this->vehicle_registration,
            'contract_type' => $this->contract_type,
            'payment_frequency' => $this->payment_frequency,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'weekly_amount' => $this->weekly_amount,
            'daily_amount' => $this->daily_amount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'deposit' => $this->deposit,
            'status' => $this->status,
            'notes' => $this->notes,
            'owner_id' => $this->owner_id,
            'employee_id' => $this->employee_id,
            'created_at' => $this->created_at?->format('Y-m-d'),
            'updated_at' => $this->updated_at?->format('Y-m-d'),
            // Computed fields
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'progress' => $this->progress,
            'days_remaining' => $this->days_remaining,
            'is_fully_paid' => $this->is_fully_paid,
            'contract_type_label' => $this->contract_type_label,
            'payment_frequency_label' => $this->payment_frequency_label,
            'total_amount_formatted' => $this->total_amount_formatted,
            'paid_amount_formatted' => $this->paid_amount_formatted,
            'remaining_amount_formatted' => $this->remaining_amount_formatted,
            'deposit_formatted' => $this->deposit_formatted,
            'weekly_amount_formatted' => $this->weekly_amount_formatted,
            'daily_amount_formatted' => $this->daily_amount_formatted,
        ];
    }
}