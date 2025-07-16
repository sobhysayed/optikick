<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test the maskEmail method with various email formats.
     */
    public function test_mask_email_masks_correctly()
    {
        // Use reflection to access the private maskEmail method
        $controller = new \App\Http\Controllers\API\Auth\AuthController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('maskEmail');
        $method->setAccessible(true);

        $this->assertEquals('s***y@example.com', $method->invoke($controller, 'sobhy@example.com'));
        $this->assertEquals('s********r@example.com', $method->invoke($controller, 'sayedanwar@example.com'));
        $this->assertEquals('a********d@example.com', $method->invoke($controller, 'ahmedsayed@example.com'));
        $this->assertEquals('a******i@example.com', $method->invoke($controller, 'ahmedali@example.com'));
    }
}
