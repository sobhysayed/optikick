<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthInfoCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'icon_key'
    ];

    public function scopeGetDailyCards($query)
    {
        $seed = date('Y-m-d');
        return $query->inRandomOrder($seed)
            ->take(4)
            ->get();
    }
}