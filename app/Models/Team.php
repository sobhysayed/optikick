<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    protected $fillable = [
        'name',
        'coach_id',
        'logo',
        'description',
        'location',
        'status',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_player', 'team_id', 'player_id')
            ->where('role', 'player');
    }
}