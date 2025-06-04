<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\HealthInfoCard;

class HealthInfoCardController extends Controller
{
    public function getDailyCards()
    {
        $cards = HealthInfoCard::getDailyCards();
        
        return response()->json([
            'cards' => $cards->map(function ($card) {
                return [
                    'id' => $card->id,
                    'title' => $card->title,
                    'content' => $card->content,
                    'icon_key' => $card->icon_key
                ];
            })
        ]);
    }
}