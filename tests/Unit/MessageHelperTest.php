<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\MessageHelper;
use App\Models\Message;
use Carbon\Carbon;

class MessageHelperTest extends TestCase
{
    public function test_text_message_preview()
    {
        $message = new Message([
            'type' => 'text',
            'content' => str_repeat('A', 60)
        ]);

        $preview = MessageHelper::getMessagePreview($message);

        $this->assertEquals(50, strlen($preview));
        $this->assertEquals(substr($message->content, 0, 50), $preview);
    }

    public function test_voice_message_preview()
    {
        $message = new Message(['type' => 'voice']);
        $preview = MessageHelper::getMessagePreview($message);

        $this->assertEquals('Sent a voice message', $preview);
    }

    public function test_photo_message_preview()
    {
        $message = new Message(['type' => 'photo']);
        $preview = MessageHelper::getMessagePreview($message);

        $this->assertEquals('Sent a photo', $preview);
    }

    public function test_unknown_message_preview()
    {
        $message = new Message(['type' => 'unknown']);
        $preview = MessageHelper::getMessagePreview($message);

        $this->assertEquals('Sent a message', $preview);
    }
}
