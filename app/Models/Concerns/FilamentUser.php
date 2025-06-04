<?php

namespace App\Models\Concerns;

trait FilamentUser
{
    public function canAccessFilament(): bool
    {
        return true;
    }

    public function getFilamentName(): string
    {
        return $this->name ?? 'Admin User';
    }
}