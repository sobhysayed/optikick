<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProgram extends Model
{
    protected $fillable = [
        'player_id',
        'doctor_id',
        'exercises',
        'focus_area',
        'status',
        'ai_generated',
        'approved_at'
    ];

    protected $casts = [
        'exercises' => 'array',
        'ai_generated' => 'boolean',
        'approved_at' => 'datetime'
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($program) {
            $player = $program->player;
            $player->notifyCoach(
                'New Training Program',
                "A new training program has been created for {$player->name}",
                'program_created',
                ['program_id' => $program->id]
            );
        });
    
        static::updated(function ($program) {
            if ($program->isDirty('status')) {
                $player = $program->player;
                $player->notifyCoach(
                    'Training Program Update',
                    "{$player->name}'s training program status: {$program->status}",
                    'program_update',
                    ['program_id' => $program->id]
                );
            }
        });
    }
}
