<?php

namespace App\Helpers;

class NotificationFormatter
{
    public static function format($notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'sender' => [
                'name' => $notification->sender ? 'DR. ' . $notification->sender->name : null,
                'username' => $notification->sender && $notification->sender->email
                    ? '@' . explode('@', $notification->sender->email)[0]
                    : null,
            ],
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
            'is_pinned' => $notification->is_pinned,
            'navigate_to' => $notification->navigate_to,
        ];
    }
}
