<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Owner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Business Information
        'business_name',
        'business_license',
        'business_address',
        'business_phone',
        'business_email',
        'business_website',
        'business_description',
        
        // Verification
        'is_verified',
        'verification_document',
        'verified_at',
        'verified_by',
        
        // Tax & Registration
        'tax_id',
        'registration_number',
        'tin_number',
        'vat_number',
        
        // Banking
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        
        // Statistics
        'total_vehicles',
        'total_earnings',
        'total_bookings',
        'total_employees',
        'rating',
        'reviews_count',
        
        // Contact
        'emergency_contact',
        'emergency_phone',
        
        // Settings
        'settings',
        'preferences',
        
        // Relational
        'user_id',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'total_earnings' => 'decimal:2',
        'rating' => 'decimal:2',
        'settings' => 'array',
        'preferences' => 'array',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user associated with the owner.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all vehicles owned by this owner.
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get all employees working for this owner.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all bookings for this owner's vehicles.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get all payments received by this owner.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all contracts for this owner.
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get the user who verified this owner.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only include verified owners.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to only include unverified owners.
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope to search by business name.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('business_name', 'like', "%{$search}%")
                     ->orWhere('business_license', 'like', "%{$search}%")
                     ->orWhere('business_address', 'like', "%{$search}%")
                     ->orWhere('tax_id', 'like', "%{$search}%");
    }

    /**
     * Scope to get owners with earnings above amount.
     */
    public function scopeEarningsAbove($query, $amount)
    {
        return $query->where('total_earnings', '>=', $amount);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the owner's full business name.
     */
    public function getFullBusinessNameAttribute()
    {
        return $this->business_name;
    }

    /**
     * Get the owner's verification status label.
     */
    public function getVerificationStatusAttribute()
    {
        return $this->is_verified ? 'Verified' : 'Unverified';
    }

    /**
     * Get the owner's verification status color.
     */
    public function getVerificationColorAttribute()
    {
        return $this->is_verified ? '#00E5FF' : '#ff6b6b';
    }

    /**
     * Get formatted total earnings.
     */
    public function getTotalEarningsFormattedAttribute()
    {
        return 'TSh ' . number_format($this->total_earnings ?? 0, 0);
    }

    /**
     * Get formatted rating.
     */
    public function getRatingFormattedAttribute()
    {
        return number_format($this->rating ?? 0, 1);
    }

    /**
     * Get the owner's full address.
     */
    public function getFullAddressAttribute()
    {
        return $this->business_address;
    }

    /**
     * Check if owner has vehicles.
     */
    public function getHasVehiclesAttribute()
    {
        return $this->total_vehicles > 0;
    }

    // ==================== MUTATORS ====================

    /**
     * Set default values on creation.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($owner) {
            if (empty($owner->total_vehicles)) {
                $owner->total_vehicles = 0;
            }
            if (empty($owner->total_earnings)) {
                $owner->total_earnings = 0;
            }
            if (empty($owner->total_bookings)) {
                $owner->total_bookings = 0;
            }
            if (empty($owner->total_employees)) {
                $owner->total_employees = 0;
            }
            if (empty($owner->rating)) {
                $owner->rating = 0;
            }
            if (empty($owner->reviews_count)) {
                $owner->reviews_count = 0;
            }
            if (empty($owner->is_verified)) {
                $owner->is_verified = false;
            }
        });
    }

    // ==================== FEATURE FLAGS ====================

    /**
     * Check if AI features are enabled for this owner.
     */
    public function isAiEnabled(): bool
    {
        return data_get($this->settings, 'ai_enabled', false) === true;
    }

    /**
     * Toggle AI features on/off.
     */
    public function toggleAi(): bool
    {
        $settings = $this->settings ?? [];
        $current = data_get($settings, 'ai_enabled', false);
        data_set($settings, 'ai_enabled', !$current);
        $this->update(['settings' => $settings]);
        return !$current;
    }

    /**
     * Enable or disable AI features.
     */
    public function setAiEnabled(bool $enabled): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, 'ai_enabled', $enabled);
        $this->update(['settings' => $settings]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Verify the owner.
     */
    public function verify($userId = null)
    {
        $this->is_verified = true;
        $this->verified_at = now();
        $this->verified_by = $userId;
        $this->save();

        return $this;
    }

    /**
     * Unverify the owner.
     */
    public function unverify()
    {
        $this->is_verified = false;
        $this->verified_at = null;
        $this->verified_by = null;
        $this->save();

        return $this;
    }

    /**
     * Update owner statistics.
     */
    public function updateStats()
    {
        $this->total_vehicles = $this->vehicles()->count();
        $this->total_employees = $this->employees()->count();
        $this->total_bookings = $this->bookings()->count();
        $this->total_earnings = $this->payments()->where('status', 'paid')->sum('amount');
        $this->save();

        return $this;
    }

    /**
     * Get owner dashboard statistics.
     */
    public function getDashboardStats()
    {
        return [
            'total_vehicles' => $this->total_vehicles,
            'available_vehicles' => $this->vehicles()->where('status', 'available')->count(),
            'rented_vehicles' => $this->vehicles()->where('status', 'rented')->count(),
            'maintenance_vehicles' => $this->vehicles()->where('status', 'maintenance')->count(),
            'total_employees' => $this->total_employees,
            'active_employees' => $this->employees()->where('status', 'active')->count(),
            'total_bookings' => $this->total_bookings,
            'pending_bookings' => $this->bookings()->where('status', 'pending')->count(),
            'active_bookings' => $this->bookings()->whereIn('status', ['confirmed', 'active'])->count(),
            'completed_bookings' => $this->bookings()->where('status', 'completed')->count(),
            'total_earnings' => $this->total_earnings,
            'monthly_earnings' => $this->payments()
                ->where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
        ];
    }

    /**
     * Check if owner is verified.
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * Get owner's business details.
     */
    public function getBusinessDetails()
    {
        return [
            'business_name' => $this->business_name,
            'business_license' => $this->business_license,
            'business_address' => $this->business_address,
            'business_phone' => $this->business_phone,
            'business_email' => $this->business_email,
            'business_website' => $this->business_website,
            'business_description' => $this->business_description,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at?->toDateTimeString(),
        ];
    }

    /**
     * Get owner's banking details.
     */
    public function getBankingDetails()
    {
        return [
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_name' => $this->bank_account_name,
        ];
    }

    /**
     * Format owner data for API response.
     */
    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            
            // Business Info
            'business_name' => $this->business_name,
            'business_license' => $this->business_license,
            'business_address' => $this->business_address,
            'business_phone' => $this->business_phone,
            'business_email' => $this->business_email,
            'business_website' => $this->business_website,
            'business_description' => $this->business_description,
            
            // Verification
            'is_verified' => $this->is_verified,
            'verification_status' => $this->verification_status,
            'verification_color' => $this->verification_color,
            'verified_at' => $this->verified_at?->toDateTimeString(),
            'verification_document' => $this->verification_document,
            
            // Tax & Registration
            'tax_id' => $this->tax_id,
            'registration_number' => $this->registration_number,
            'tin_number' => $this->tin_number,
            'vat_number' => $this->vat_number,
            
            // Banking
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_name' => $this->bank_account_name,
            
            // Statistics
            'total_vehicles' => $this->total_vehicles,
            'total_earnings' => $this->total_earnings,
            'total_earnings_formatted' => $this->total_earnings_formatted,
            'total_bookings' => $this->total_bookings,
            'total_employees' => $this->total_employees,
            'rating' => $this->rating,
            'rating_formatted' => $this->rating_formatted,
            'reviews_count' => $this->reviews_count,
            'has_vehicles' => $this->has_vehicles,
            
            // Contact
            'emergency_contact' => $this->emergency_contact,
            'emergency_phone' => $this->emergency_phone,
            
            // Settings
            'settings' => $this->settings,
            'preferences' => $this->preferences,
            'ai_enabled' => $this->isAiEnabled(),
            
            // Timestamps
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            
            // User data (if loaded)
            'user' => $this->user?->toApiResponse(),
        ];
    }

    /**
     * Get owner for dropdown/select.
     */
    public static function getSelectList($verified = null)
    {
        $query = self::query();
        
        if ($verified !== null) {
            $query->where('is_verified', $verified);
        }
        
        return $query->orderBy('business_name')
            ->get()
            ->map(fn($owner) => [
                'id' => $owner->id,
                'business_name' => $owner->business_name,
                'business_email' => $owner->business_email,
                'business_phone' => $owner->business_phone,
                'is_verified' => $owner->is_verified,
            ]);
    }

    /**
     * Get top earning owners.
     */
    public static function getTopEarners($limit = 10)
    {
        return self::orderBy('total_earnings', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recently registered owners.
     */
    public static function getRecent($limit = 10)
    {
        return self::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get owner statistics.
     */
    public static function getStats()
    {
        return [
            'total_owners' => self::count(),
            'verified_owners' => self::where('is_verified', true)->count(),
            'unverified_owners' => self::where('is_verified', false)->count(),
            'total_vehicles' => self::sum('total_vehicles'),
            'total_earnings' => self::sum('total_earnings'),
            'total_bookings' => self::sum('total_bookings'),
            'total_employees' => self::sum('total_employees'),
            'average_rating' => self::avg('rating'),
        ];
    }
}