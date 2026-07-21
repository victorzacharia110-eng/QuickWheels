<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'owner_id',
        'document_type',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_mime_type',
        'file_size',
        'ai_analysis',
        'ai_analyzed_at',
        'expires_at',
        'is_verified',
    ];

    protected $casts = [
        'ai_analysis' => 'array',
        'ai_analyzed_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_verified' => 'boolean',
        'file_size' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) return null;
        return (int) now()->diffInDays($this->expires_at, false);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) return 'Unknown';
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'document_type' => $this->document_type,
            'title' => $this->title,
            'description' => $this->description,
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'file_mime_type' => $this->file_mime_type,
            'file_size' => $this->file_size,
            'file_size_formatted' => $this->file_size_formatted,
            'url' => $this->url,
            'ai_analysis' => $this->ai_analysis,
            'ai_analyzed_at' => $this->ai_analyzed_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'is_expired' => $this->is_expired,
            'days_until_expiry' => $this->days_until_expiry,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
