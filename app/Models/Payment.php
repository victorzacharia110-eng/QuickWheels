<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'driver_name',
        'driver_id',
        'amount',
        'method',
        'status',
        'date',
        'notes',
        'transaction_id',
        'payment_method',
        'reference_number',
        'paid_at',
        'approved_by',
        'owner_id',
        'employee_id',
        'booking_id',
        'payment_date',
        'payment_type',
        'receipt_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'payment_date' => 'date',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the contract associated with this payment
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the driver (user) who made this payment
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Get the booking associated with this payment
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the owner who received this payment
     */
    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Get the employee who processed this payment
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who approved this payment
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope for paid payments
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for payments by contract
     */
    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    /**
     * Scope for payments by driver
     */
    public function scopeByDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Scope for payments by driver name
     */
    public function scopeByDriverName($query, $driverName)
    {
        return $query->where('driver_name', 'like', "%{$driverName}%");
    }

    /**
     * Scope for payments by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for payments by method
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Scope for payments between dates
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for payments in current month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date', now()->month)
                     ->whereYear('date', now()->year);
    }

    /**
     * Scope for payments today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the status label (matches frontend)
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'paid' => 'Paid',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get the status color (matches frontend)
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'paid' => '#00E5FF',
            'pending' => '#FFD93D',
            'failed' => '#ff6b6b',
            'completed' => '#4ADE80',
            'cancelled' => '#ff6b6b',
            'refunded' => '#FFD93D',
        ];
        return $colors[$this->status] ?? 'rgba(255,255,255,0.3)';
    }

    /**
     * Get the method label
     */
    public function getMethodLabelAttribute()
    {
        $labels = [
            'Cash' => 'Cash',
            'M-Pesa' => 'M-Pesa',
            'Airtel Money' => 'Airtel Money',
            'Tigo Pesa' => 'Tigo Pesa',
            'Bank Transfer' => 'Bank Transfer',
            'Credit Card' => 'Credit Card',
            'Mobile Money' => 'Mobile Money',
        ];
        return $labels[$this->method] ?? $this->method;
    }

    /**
     * Get the payment type label
     */
    public function getPaymentTypeLabelAttribute()
    {
        $labels = [
            'deposit' => 'Deposit',
            'installment' => 'Installment',
            'full' => 'Full Payment',
            'penalty' => 'Penalty',
            'refund' => 'Refund',
        ];
        return $labels[$this->payment_type] ?? $this->payment_type;
    }

    /**
     * Get formatted amount
     */
    public function getAmountFormattedAttribute()
    {
        return 'TSh ' . number_format($this->amount, 0);
    }

    /**
     * Get the date formatted
     */
    public function getDateFormattedAttribute()
    {
        if (!$this->date) return '—';
        return $this->date->format('Y-m-d');
    }

    /**
     * Get the date displayed nicely
     */
    public function getDateDisplayAttribute()
    {
        if (!$this->date) return '—';
        return $this->date->format('M d, Y');
    }

    /**
     * Get paid at formatted
     */
    public function getPaidAtFormattedAttribute()
    {
        if (!$this->paid_at) return '—';
        return $this->paid_at->format('Y-m-d H:i:s');
    }

    /**
     * Get the payment status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'paid' => 'badge-success',
            'pending' => 'badge-warning',
            'failed' => 'badge-danger',
            'completed' => 'badge-success',
            'cancelled' => 'badge-secondary',
            'refunded' => 'badge-info',
        ];
        return $classes[$this->status] ?? 'badge-secondary';
    }

    /**
     * Check if payment is paid
     */
    public function getIsPaidAttribute()
    {
        return $this->status === 'paid' || $this->status === 'completed';
    }

    /**
     * Check if payment is pending
     */
    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is failed
     */
    public function getIsFailedAttribute()
    {
        return $this->status === 'failed';
    }

    // ==================== MUTATORS ====================

    /**
     * Set default values on creation
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->status)) {
                $payment->status = 'pending';
            }
            if (empty($payment->date)) {
                $payment->date = now();
            }
            if (empty($payment->receipt_number)) {
                $payment->receipt_number = $payment->generateReceiptNumber();
            }
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = $payment->generateTransactionId();
            }
        });
    }

    // ==================== HELPER METHODS ====================

    /**
     * Generate a unique receipt number
     */
    public function generateReceiptNumber()
    {
        return 'RCP-' . date('Ymd') . '-' . strtoupper(uniqid());
    }

    /**
     * Generate a unique transaction ID
     */
    public function generateTransactionId()
    {
        return 'TXN-' . date('Ymd') . '-' . strtoupper(uniqid());
    }

    /**
     * Approve a pending payment
     */
    public function approve($userId = null)
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Only pending payments can be approved');
        }

        $this->status = 'paid';
        $this->paid_at = now();
        $this->approved_by = $userId;
        $this->save();

        return $this;
    }

    /**
     * Reject a pending payment
     */
    public function reject()
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Only pending payments can be rejected');
        }

        $this->status = 'failed';
        $this->save();

        return $this;
    }

    /**
     * Mark payment as completed
     */
    public function complete()
    {
        $this->status = 'completed';
        $this->paid_at = now();
        $this->save();

        return $this;
    }

    /**
     * Refund a payment
     */
    public function refund()
    {
        if (!in_array($this->status, ['paid', 'completed'])) {
            throw new \Exception('Only paid/completed payments can be refunded');
        }

        $this->status = 'refunded';
        $this->save();

        return $this;
    }

    /**
     * Check if payment can be approved
     */
    public function canApprove()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment can be rejected
     */
    public function canReject()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment can be refunded
     */
    public function canRefund()
    {
        return in_array($this->status, ['paid', 'completed']);
    }

    /**
     * Get payments grouped by method (matches frontend computed)
     */
    public static function getGroupedByMethod($ownerId = null)
    {
        $query = self::query();
        
        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }

        $payments = $query->get();
        $methods = [];

        foreach ($payments as $payment) {
            $method = $payment->method ?? 'Cash';
            if (!isset($methods[$method])) {
                $methods[$method] = [];
            }
            $methods[$method][] = $payment->toApiResponse();
        }

        return $methods;
    }

    /**
     * Get payment statistics (matches frontend computed properties)
     */
    public static function getStats($ownerId = null)
    {
        $query = self::query();

        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }

        return [
            'total' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('amount'),
            'paid' => (clone $query)->where('status', 'paid')->count(),
            'paid_amount' => (clone $query)->where('status', 'paid')->sum('amount'),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'pending_amount' => (clone $query)->where('status', 'pending')->sum('amount'),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'failed_amount' => (clone $query)->where('status', 'failed')->sum('amount'),
            'this_month' => (clone $query)->whereMonth('date', now()->month)->sum('amount'),
            'today' => (clone $query)->whereDate('date', today())->sum('amount'),
        ];
    }

    /**
     * Get payments by contract with totals
     */
    public static function getByContractWithTotals($contractId)
    {
        $payments = self::where('contract_id', $contractId)->get();
        
        return [
            'payments' => $payments->map(fn($p) => $p->toApiResponse()),
            'total_paid' => $payments->where('status', 'paid')->sum('amount'),
            'total_pending' => $payments->where('status', 'pending')->sum('amount'),
            'count' => $payments->count(),
        ];
    }

    /**
     * Get payment method statistics
     */
    public static function getMethodStats($ownerId = null)
    {
        $query = self::query();

        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }

        return $query->select('method', \DB::raw('count(*) as count'), \DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->method => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]
            ]);
    }

    /**
     * Get daily payment summary
     */
    public static function getDailySummary($date = null)
    {
        $date = $date ?? today();

        return [
            'date' => $date->format('Y-m-d'),
            'total_payments' => self::whereDate('date', $date)->count(),
            'total_amount' => self::whereDate('date', $date)->sum('amount'),
            'paid_amount' => self::whereDate('date', $date)->where('status', 'paid')->sum('amount'),
            'pending_amount' => self::whereDate('date', $date)->where('status', 'pending')->sum('amount'),
            'failed_amount' => self::whereDate('date', $date)->where('status', 'failed')->sum('amount'),
        ];
    }

    /**
     * Get weekly payment summary
     */
    public static function getWeeklySummary()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return [
            'start_date' => $startOfWeek->format('Y-m-d'),
            'end_date' => $endOfWeek->format('Y-m-d'),
            'total_payments' => self::whereBetween('date', [$startOfWeek, $endOfWeek])->count(),
            'total_amount' => self::whereBetween('date', [$startOfWeek, $endOfWeek])->sum('amount'),
            'paid_amount' => self::whereBetween('date', [$startOfWeek, $endOfWeek])
                ->where('status', 'paid')->sum('amount'),
        ];
    }

    /**
     * Convert to API response (matches frontend store)
     */
    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'driver_name' => $this->driver_name,
            'driver_id' => $this->driver_id,
            'amount' => $this->amount,
            'method' => $this->method,
            'status' => $this->status,
            'date' => $this->date_formatted,
            'notes' => $this->notes,
            'transaction_id' => $this->transaction_id,
            'reference_number' => $this->reference_number,
            'receipt_number' => $this->receipt_number,
            'payment_method' => $this->payment_method,
            'payment_type' => $this->payment_type,
            'payment_date' => $this->payment_date,
            'paid_at' => $this->paid_at_formatted,
            'approved_by' => $this->approved_by,
            'booking_id' => $this->booking_id,
            'owner_id' => $this->owner_id,
            'employee_id' => $this->employee_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            // Computed fields
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'amount_formatted' => $this->amount_formatted,
            'date_display' => $this->date_display,
            'method_label' => $this->method_label,
            'is_paid' => $this->is_paid,
            'is_pending' => $this->is_pending,
            'is_failed' => $this->is_failed,
        ];
    }
}