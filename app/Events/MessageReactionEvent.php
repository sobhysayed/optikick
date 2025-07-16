<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReactionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $reaction;

    public function __construct($messageId, $reaction)
    {
        $this->messageId = $messageId;
        $this->reaction = $reaction;
    }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->userId);
    }
}