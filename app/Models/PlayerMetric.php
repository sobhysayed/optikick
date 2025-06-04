<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerMetric extends Model
{
    protected $fillable = [
        'player_id',
        'resting_hr',
        'max_hr',
        'hrv',
        'vo2_max',
        'weight',
        'reaction_time',
        'match_consistency',
        'minutes_played',
        'training_hours',
        'injury_frequency',
        'recovery_score',
        'fatigue_score',
        'injury_risk',
        'readiness_score',
        'recorded_at'
    ];

    protected $casts = [
        'recorded_at' => 'date'
    ];

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($metric) {
            $player = $metric->player;
            
            // Determine which metrics changed significantly
            $significantChanges = [];
            if ($metric->fatigue_score > 70) {
                $significantChanges[] = "high fatigue";
            }
            if ($metric->injury_risk > 70) {
                $significantChanges[] = "elevated injury risk";
            }
            
            if (!empty($significantChanges)) {
                $player->notifyCoach(
                    'Player Metric Alert',
                    "{$player->name} shows " . implode(' and ', $significantChanges),
                    'metric_alert',
                    ['metric_id' => $metric->id]
                );
            }
        });
    }
}
