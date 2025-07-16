<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\NotificationFormatter;
use Illuminate\Support\Carbon;

class NotificationFormatterTest extends TestCase
{
    public function test_format_notification_with_sender()
    {
        $notification = (object)[
            'id' => 1,
            'type' => 'message',
            'title' => 'New message',
            'body' => 'You got a message',
            'sender' => (object)[
                'name' => 'Hassan',
                'email' => 'hassan7@example.com'
            ],
            'read_at' => null,
            'created_at' => Carbon::parse('2025-07-01 12:00:00'),
            'is_pinned' => true,
            'navigate_to' => 'chat/1'
        ];

        $formatted = NotificationFormatter::format($notification);

        $this->assertEquals('DR. Hassan', $formatted['sender']['name']);
        $this->assertEquals('@hassan7', $formatted['sender']['username']);
        $this->assertEquals('message', $formatted['type']);
        $this->assertEquals('New message', $formatted['title']);
        $this->assertEquals('chat/1', $formatted['navigate_to']);
        $this->assertTrue($formatted['is_pinned']);
    }

    // Edge case
    public function test_format_notification_without_sender()
    {
        $notification = (object)[
            'id' => 2,
            'type' => 'system',
            'title' => 'Welcome',
            'body' => 'Welcome to the app!',
            'sender' => null,
            'read_at' => null,
            'created_at' => now(),
            'is_pinned' => false,
            'navigate_to' => null
        ];

        $formatted = NotificationFormatter::format($notification);

        $this->assertNull($formatted['sender']['name']);
        $this->assertNull($formatted['sender']['username']);
        $this->assertEquals('system', $formatted['type']);
    }

    public function test_format_notification_sender_with_malformed_email()
    {
        $notification = (object)[
            'id' => 4,
            'type' => 'alert',
            'title' => 'Bad Email',
            'body' => 'Sender has bad email format',
            'sender' => (object)[
                'name' => 'Bot User',
                'email' => 'not-an-email'
            ],
            'read_at' => null,
            'created_at' => now(),
            'is_pinned' => false,
            'navigate_to' => null
        ];

        $formatted = NotificationFormatter::format($notification);

        $this->assertEquals('DR. Bot User', $formatted['sender']['name']);
        $this->assertEquals('@not-an-email', $formatted['sender']['username']);
    }
}
