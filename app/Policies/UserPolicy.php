<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function approveClassification(User $user, User $player)
    {
        // Only doctors can approve classifications
        return $user->role === 'doctor' && $player->role === 'player';
    }
    
} 