<?php

namespace App\Helpers;

use App\Models\Message;
use Carbon\Carbon;

class MessageHelper
{
    public static function getMessagePreview(Message $message): string
    {
        return match ($message->type) {
            'text' => substr($message->content, 0, 50),
            'voice' => 'Sent a voice message',
            'photo' => 'Sent a photo',
            default => 'Sent a message',
        };
    }

}
