<?php

namespace App\Policies;

use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingProgramPolicy
{
    use HandlesAuthorization;

    public function approveProgram(User $user, TrainingProgram $program)
    {
        // Only doctors can approve programs
        return $user->role === 'doctor';
    }
} 