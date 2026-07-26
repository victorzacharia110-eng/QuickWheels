<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OwnerReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'vehicle_id',
        'technician_id',
        'title',
        'description',
        'status',
        'submitted_at',
        'questions',
        'technician_notes',
        'technician_signature',
        'technician_signed_at',
        'owner_notes',
        'owner_signature',
        'owner_signed_at',
        'completed_at',
    ];

    protected $casts = [
        'questions' => 'array',
        'submitted_at' => 'datetime',
        'technician_signed_at' => 'datetime',
        'owner_signed_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    // ==================== WORKFLOW ====================

    public function review()
    {
        $this->update(['status' => 'reviewed']);
    }

    public function verify($ownerSignature = null, $ownerNotes = null)
    {
        $this->update([
            'status' => 'verified',
            'owner_signature' => $ownerSignature ?? $this->owner_signature,
            'owner_notes' => $ownerNotes ?? $this->owner_notes,
            'owner_signed_at' => now(),
        ]);
    }

    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function submit()
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function submitAnswers($answers)
    {
        $questions = $this->questions ?? [];
        foreach ($answers as $answer) {
            $qIndex = array_search($answer['question_id'], array_column($questions, 'id'));
            if ($qIndex !== false) {
                $questions[$qIndex]['answer'] = $answer['answer'];
                $questions[$qIndex]['answered_at'] = now()->toDateTimeString();
            }
        }
        $this->update(['questions' => $questions]);
    }

    public function setQuestions($questions)
    {
        $indexed = array_map(function ($q, $i) {
            if (!isset($q['id'])) {
                $q['id'] = $i + 1;
            }
            if (!isset($q['type'])) {
                $q['type'] = 'text';
            }
            if (!isset($q['required'])) {
                $q['required'] = false;
            }
            return $q;
        }, $questions, array_keys($questions));

        $this->update(['questions' => $indexed]);
    }

    // ==================== API RESPONSE ====================

    public function toApiResponse()
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'vehicle_id' => $this->vehicle_id,
            'technician_id' => $this->technician_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'questions' => $this->questions ?? [],
            'technician_notes' => $this->technician_notes,
            'technician_signature' => $this->technician_signature,
            'technician_signed_at' => $this->technician_signed_at?->toDateTimeString(),
            'owner_notes' => $this->owner_notes,
            'owner_signature' => $this->owner_signature,
            'owner_signed_at' => $this->owner_signed_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'vehicle' => $this->whenLoaded('vehicle', fn() => [
                'id' => $this->vehicle->id,
                'name' => $this->vehicle->name,
                'registration' => $this->vehicle->registration,
            ]),
            'technician' => $this->whenLoaded('technician', fn() => [
                'id' => $this->technician->id,
                'name' => $this->technician->name,
            ]),
        ];
    }
}
