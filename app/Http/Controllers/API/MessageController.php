<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Events\NewMessageEvent;
use App\Events\MessageReadEvent;
use App\Events\MessageReactionEvent;

class MessageController extends Controller
{
    private const ALLOWED_PHOTO_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp'
    ];

    private const ALLOWED_VOICE_TYPES = [
        'audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/webm'
    ];

    private const MAX_PHOTO_SIZE = 10240; // 10MB
    private const MAX_VOICE_SIZE = 5120;  // 5MB

    public function getConversations(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $conversations = $this->buildConversationQuery($request, $userId)
            ->get()
            ->map(fn($conversation) => $this->formatConversation($conversation, $userId));

        return response()->json($conversations);
    }

    private function buildConversationQuery(Request $request, int $userId)
    {
        $query = $request->input('query');

        return Conversation::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('participant_id', $userId);
        })
            ->with(['lastMessage', 'user.profile', 'participant.profile'])
            ->when($query, function ($q) use ($query, $userId) {
                $queryString = ltrim($query);
                $isUsername = str_starts_with($queryString, '@');
                $queryValue = $isUsername ? substr($queryString, 1) : $queryString;

                return $q->where(function ($subQ) use ($isUsername, $queryValue, $userId) {
                    $relationFilter = fn($relation) =>
                    $relation->where('id', '!=', $userId)
                        ->where(function ($q) use ($isUsername, $queryValue) {
                            if ($isUsername) {
                                $q->where('email', 'like', "{$queryValue}@%");
                            } else {
                                $q->where('name', 'like', "%{$queryValue}%")
                                    ->orWhere('email', 'like', "%{$queryValue}%");
                            }
                        });

                    $subQ->whereHas('user', $relationFilter)
                        ->orWhereHas('participant', $relationFilter);
                });
            })
            ->latest('last_message_at');
    }

    private function formatConversation($conversation, int $userId): array
    {
        $otherUser = $this->getOtherParticipant($conversation, $userId);

        return [
            'id' => $conversation->id,
            'participant' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'username' => '@' . strstr($otherUser->email, '@', true),
                'avatar' => optional($otherUser->profile)->avatar_url,
            ],
            'last_message' => $conversation->lastMessage ? [
                'id' => $conversation->lastMessage->id,
                'type' => $conversation->lastMessage->type,
                'content' => $this->getMessagePreview($conversation->lastMessage),
                'created_at' => optional($conversation->lastMessage->created_at)?->diffForHumans(),
                'is_read' => !is_null($conversation->lastMessage->read_at),
            ] : null,
            'unread_count' => $conversation->messages()
                ->where('recipient_id', $userId)
                ->whereNull('read_at')
                ->count(),
        ];
    }

    private function getOtherParticipant($conversation, int $userId)
    {
        return $conversation->user_id === $userId
            ? $conversation->participant
            : $conversation->user;
    }


    public function getMessages(User $user)
    {
        $conversation = $this->findOrCreateConversation($user);

        // Mark messages as delivered
        $conversation->messages()
            ->where('recipient_id', auth()->id())
            ->where('status', 'sent')
            ->update([
                'status' => 'delivered',
                'delivered_at' => now()
            ]);

        $messages = $conversation->messages()
            ->with(['sender.profile'])
            ->latest()
            ->paginate(50)
            ->through(function ($message) {
                return [
                    'id' => $message->id,
                    'type' => $message->type,
                    'content' => $message->content,
                    'file_url' => $message->file_url,
                    'reaction' => $message->reaction,
                    'created_at' => $this->formatMessageTime($message->created_at),
                    'status' => $message->status,
                    'delivered_at' => $message->delivered_at,
                    'read_at' => $message->read_at,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                        'avatar' => $message->sender->profile ? $message->sender->profile->avatar_url : null,
                        'role' => $message->sender->role
                    ]
                ];
            });

        // Mark messages as read
        $conversation->messages()
            ->where('recipient_id', auth()->id())
            ->whereIn('status', ['sent', 'delivered'])
            ->update([
                'status' => 'read',
                'read_at' => now()
            ]);

        return response()->json($messages);
    }

    private function findOrCreateConversation(User $participant)
    {
        // Ensure we have a valid authenticated user
        if (!($userId = auth()->id())) {
            throw new \Exception('User not authenticated');
        }

        // Ensure participant exists and is different from authenticated user
        if ($userId === $participant->id) {
            throw new \Exception('You cannot start a conversation with yourself.');
        }


        // Try to find existing conversation
        $conversation = Conversation::where(function($q) use ($userId, $participant) {
            $q->where('user_id', $userId)->where('participant_id', $participant->id)
              ->orWhere('user_id', $participant->id)->where('participant_id', $userId);
        })->first();

        // Create new conversation if none exists
        if (!$conversation) {
            try {
                $conversation = Conversation::create([
                    'user_id' => $userId,
                    'participant_id' => $participant->id,
                    'last_message_at' => now()
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create conversation', [
                    'user_id' => $userId,
                    'participant_id' => $participant->id,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to create conversation');
            }
        }

        return $conversation;
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $query = $request->input('query');
        $authId = auth()->id();

        $users = User::where('id', '!=', $authId)
            ->where('role', '!=', 'admin')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->with('profile')
            ->take(10)
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => '@' . strstr($user->email, '@', true),
                'avatar' => optional($user->profile)->avatar_url,
            ]);

        return response()->json($users);
    }

    public function sendMessage(Request $request, $recipientId)
    {
        $participant = User::find($recipientId);

        if (!$participant) {
            return response()->json(['message' => 'Recipient not found'], 404);
        }

        if ($participant->id === auth()->id()) {
            return response()->json(['message' => 'You cannot message yourself'], 400);
        }

        $this->validateMessageRequest($request);
        $conversation = $this->findOrCreateConversation($participant);

        $message = new Message([
            'sender_id' => auth()->id(),
            'recipient_id' => $participant->id,
            'type' => $request->type,
            'content' => $request->type === 'text' ? $request->input('content') : null,
            'file_url' => $request->hasFile('file') ? $this->uploadFile($request->file('file'), $request->type) : null
        ]);

        $conversation->messages()->save($message);

        $conversation->update([
            'last_message_id' => $message->id,
            'last_message_at' => now()
        ]);

        // Create notification for recipient
        $participant->notifications()->create([
            'type' => 'message',
            'title' => auth()->user()->name . ' sent you a message',
            'body' => $this->getMessagePreview($message),
            'sender_id' => auth()->id(),
            'related_message_id' => $message->id,
            'data' => [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'message_type' => $message->type
            ]
        ]);

        // Broadcast the new message event
        broadcast(new NewMessageEvent($message))->toOthers();

        // Format response
        $response = [
            'message' => [
                'id' => $message->id,
                'type' => $message->type,
                'content' => $message->content,
                'file_url' => $message->file_url,
                'created_at' => $this->formatMessageTime($message->created_at),
                'sender' => [
                    'id' => auth()->id(),
                    'name' => auth()->user()->name
                ]
            ],
            'conversation_id' => $conversation->id
        ];

        return response()->json($response);
    }

    public function markAsRead(Message $message): JsonResponse
    {
        if ($message->recipient_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->update(['read_at' => now()]);

        // Broadcast message read status
        broadcast(new MessageReadEvent($message->id, $message->sender_id))->toOthers();

        return response()->json(['message' => 'Message marked as read']);
    }

    public function react(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'reaction' => [
                'nullable',
                'string',
                'regex:/^[\x{1F300}-\x{1F9FF}]$/u' // Only single emoji allowed
            ]
        ]);

        // Ensure the user is either the sender or recipient
        if ($message->sender_id !== auth()->id() && $message->recipient_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (empty($request->reaction)) {
            // Remove the reaction
            $message->update(['reaction' => null]);
            broadcast(new MessageReactionEvent($message->id, null))->toOthers();

            return response()->json([
                'status' => 'success',
                'message' => 'Reaction removed successfully',
            ]);
        } else {
            // Add or update the reaction
            $message->update(['reaction' => $request->reaction]);
            broadcast(new MessageReactionEvent($message->id, $request->reaction))->toOthers();

            // Notify the sender if someone else reacted
            if ($message->sender_id !== auth()->id()) {
                $message->sender->notifications()->create([
                    'type' => 'reaction',
                    'title' => auth()->user()->name . ' reacted to your message',
                    'body' => auth()->user()->name . ' added reaction ' . $request->reaction,
                    'sender_id' => auth()->id(),
                    'related_message_id' => $message->id,
                    'data' => [
                        'message_id' => $message->id,
                        'reaction' => $request->reaction
                    ]
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Reaction added successfully',
            ]);
        }
    }

    private function validateMessageRequest(Request $request)
    {
        $rules = [
            'type' => 'required|in:text,voice,photo',
            'content' => 'required_if:type,text|string|max:1000'
        ];

        if ($request->type === 'photo') {
            $rules['file'] = [
                'required',
                'file',
                'max:' . self::MAX_PHOTO_SIZE,
                'mimes:jpeg,png,gif,webp',
                function ($attribute, $value, $fail) {
                    if (!in_array($value->getMimeType(), self::ALLOWED_PHOTO_TYPES)) {
                        $fail('The file must be a valid image file.');
                    }
                }
            ];
        } elseif ($request->type === 'voice') {
            $rules['file'] = [
                'required',
                'file',
                'max:' . self::MAX_VOICE_SIZE,
                'mimes:mpga,mp3,wav,ogg,m4a',
                function ($attribute, $value, $fail) {
                    if (!in_array($value->getMimeType(), self::ALLOWED_VOICE_TYPES)) {
                        $fail('The file must be a valid audio file.');
                    }
                }
            ];
        }

        $request->validate($rules);
    }

    private function uploadFile($file, $type)
    {
        try {
            if (!$file->isValid()) {
                throw new \Exception('Invalid file upload');
            }

            // Validate file size
            $maxSize = $type === 'photo' ? self::MAX_PHOTO_SIZE : self::MAX_VOICE_SIZE;
            if ($file->getSize() > $maxSize * 1024) {
                throw new \Exception('File size exceeds maximum limit');
            }

            // Generate a unique filename
            $filename = uniqid(auth()->id() . '_') . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Determine the storage path based on type
            $path = $type === 'photo' ? 'messages/photos' : 'messages/voice';

            // Store the file
            $filePath = $file->storeAs($path, $filename, 'public');

            if (!$filePath) {
                throw new \Exception('Failed to store the file');
            }

            // Return the public URL
            return Storage::url($filePath);

        } catch (\Exception $e) {
            \Log::error('File upload failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType()
            ]);

            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        }
    }

    public function deleteMessage(Message $message)
    {
        if ($message->sender_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete associated file if exists
        if ($message->file_url) {
            $path = str_replace('/storage/', '', $message->file_url);
            Storage::disk('public')->delete($path);
        }

        $message->delete();
        return response()->json(['message' => 'Message deleted']);
    }

    public function getUnreadCount()
    {
        $count = Message::where('recipient_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    private function getMessagePreview(Message $message)
    {
        switch ($message->type) {
            case 'text':
                return substr($message->content, 0, 50);
            case 'voice':
                return 'Sent a voice message';
            case 'photo':
                return 'Sent a photo';
            default:
                return 'Sent a message';
        }
    }

    private function formatMessageTime($dateTime)
    {
        $now = Carbon::now();
        $messageTime = Carbon::parse($dateTime);
        $diff = $messageTime->diffInSeconds($now);

        if ($diff < 60) {
            return 'now';
        } elseif ($diff < 3600) {
            return $messageTime->diffInMinutes($now) . 'm';
        } elseif ($diff < 86400) {
            return $messageTime->diffInHours($now) . 'h';
        } else {
            return $messageTime->diffInDays($now) . 'd';
        }
    }
}
