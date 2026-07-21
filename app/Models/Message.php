<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'owner_id',
        'body',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'owner_id' => $this->owner_id,
            'body' => $this->body,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toISOString(),
            'sender' => $this->relationLoaded('sender') ? [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'role' => $this->sender->role,
            ] : null,
            'receiver' => $this->relationLoaded('receiver') ? [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
                'role' => $this->receiver->role,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
