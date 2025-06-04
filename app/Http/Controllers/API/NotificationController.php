<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Events\NewNotificationEvent;

class NotificationController extends Controller
{
    public function getNotifications()
    {
        // Get the authenticated user's notifications

        $notifications = auth()->user()
        ->notifications()
        ->latest()
        ->get()
        ->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'body' => $notification->body,
                'sender' => [
                    'name' => $notification->sender->name ?? null,
                    'username' => $notification->sender->email ? explode('@', $notification->sender->email)[0] : null,
                ],
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'is_pinned' => $notification->is_pinned,
                'metadata' => [
                    'related_program_id' => $notification->related_program_id,
                    'assessment_id' => $notification->assessment_id,
                ]
            ];
        });

        return response()->json($notifications);
    }

    public function getUnreadNotifications()
    {
        $notifications = auth()->user()
            ->notifications()
            ->whereNull('read_at')
            ->latest()
            ->get();

        return response()->json($notifications);
    }

    public function getPinnedNotifications()
    {
        $notifications = auth()->user()
            ->notifications()
            ->where('is_pinned', true)
            ->latest()
            ->get();

        return response()->json($notifications);
    }

    public function getUnreadCount()
    {
        $count = auth()->user()
            ->notifications()
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function getNotificationDetails($id)
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        return response()->json($notification);
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->update(['read_at' => now()]);

        broadcast(new NewNotificationEvent([
            'type' => 'notification_read',
            'notification_id' => $id
        ]))->toOthers();

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead()
    {
        auth()->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        broadcast(new NewNotificationEvent([
            'type' => 'all_notifications_read',
            'user_id' => auth()->id()
        ]))->toOthers();

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function pinNotification(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->update(['is_pinned' => true]);

        broadcast(new NewNotificationEvent([
            'type' => 'notification_pinned',
            'notification_id' => $notification->id
        ]))->toOthers();

        return response()->json(['message' => 'Notification pinned']);
    }

    public function unpinNotification(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->update(['is_pinned' => false]);

        broadcast(new NewNotificationEvent([
            'type' => 'notification_unpinned',
            'notification_id' => $notification->id
        ]))->toOthers();

        return response()->json(['message' => 'Notification unpinned']);
    }

    public function deleteNotification($id)
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->delete();

        broadcast(new NewNotificationEvent([
            'type' => 'notification_deleted',
            'notification_id' => $id
        ]))->toOthers();

        return response()->json(['message' => 'Notification deleted']);
    }
}
