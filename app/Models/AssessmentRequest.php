<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentRequest extends Model
{
    protected $fillable = [
        'player_id',
        'doctor_id',
        'issue_type',
        'requested_at',
        'message',
        'status'
    ];

    protected $casts = [
        'requested_at' => 'datetime'
    ];

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($assessment) {
            $player = $assessment->player;
            $player->notifyCoach(
                'New Assessment Request',
                "{$player->name} has requested an assessment for {$assessment->issue_type}",
                'assessment',
                ['assessment_id' => $assessment->id]
            );
        });
    }
}
