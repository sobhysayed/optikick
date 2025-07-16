# Unit Testing Guide

This document provides a comprehensive guide for writing and running unit tests in the Optikick application.

## Table of Contents

1. [Overview](#overview)
2. [Test Structure](#test-structure)
3. [Running Tests](#running-tests)
4. [Writing Tests](#writing-tests)
5. [Test Categories](#test-categories)
6. [Best Practices](#best-practices)
7. [Examples](#examples)

## Overview

Unit tests are automated tests that verify the functionality of individual components (units) of the application in isolation. In our Laravel application, we test:

- **Models**: Database relationships, scopes, accessors, mutators
- **Services**: Business logic, external API integrations
- **Helpers**: Utility functions and formatting logic
- **Policies**: Authorization logic
- **Commands**: Artisan commands

## Test Structure

```
tests/
├── Unit/                          # Unit tests
│   ├── TestSuite.php             # Base test class with helpers
│   ├── UserTest.php              # User model tests
│   ├── PlayerMetricTest.php      # PlayerMetric model tests
│   ├── AIModelServiceTest.php    # AI service tests
│   ├── MetricAnalysisServiceTest.php # Metric analysis tests
│   ├── MessageHelperTest.php     # Message helper tests
│   ├── NotificationFormatterTest.php # Notification tests
│   └── AuthControllerTest.php    # Auth controller tests
├── Feature/                       # Feature tests (integration)
└── TestCase.php                  # Base test case
```

## Running Tests

### Run All Unit Tests
```bash
php artisan test --testsuite=Unit
```

### Run Specific Test File
```bash
php artisan test tests/Unit/UserTest.php
```

### Run Specific Test Method
```bash
php artisan test --filter test_can_create_a_user
```

### Run Tests with Coverage
```bash
php artisan test --coverage --testsuite=Unit
```

### Run Tests in Parallel
```bash
php artisan test --parallel --testsuite=Unit
```

## Writing Tests

### Test Method Naming Convention

Use descriptive names that explain what is being tested:

```php
/** @test */
public function it_can_create_a_user()
{
    // Test implementation
}

/** @test */
public function it_validates_email_format()
{
    // Test implementation
}

/** @test */
public function it_returns_fallback_program_when_ai_api_fails()
{
    // Test implementation
}
```

### Test Structure (AAA Pattern)

Follow the Arrange-Act-Assert pattern:

```php
/** @test */
public function it_can_send_message()
{
    // Arrange - Set up test data
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $messageData = ['type' => 'text', 'content' => 'Hello'];

    // Act - Execute the method being tested
    $message = $sender->sendMessage($recipient, $messageData);

    // Assert - Verify the results
    $this->assertInstanceOf(Message::class, $message);
    $this->assertEquals($sender->id, $message->sender_id);
    $this->assertEquals($recipient->id, $message->recipient_id);
}
```

### Using TestSuite Base Class

Extend the `TestSuite` class to get access to helper methods:

```php
class UserTest extends TestSuite
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->users = $this->createTestUsers();
    }

    /** @test */
    public function it_can_create_user_with_role()
    {
        $user = $this->createUser('player', ['name' => 'John']);
        
        $this->assertModelHasAttributes($user, [
            'name' => 'John',
            'role' => 'player'
        ]);
    }
}
```

## Test Categories

### 1. Model Tests

Test model relationships, scopes, and business logic:

```php
/** @test */
public function it_has_profile_relationship()
{
    $profile = Profile::factory()->create(['user_id' => $this->user->id]);
    
    $this->assertInstanceOf(Profile::class, $this->user->profile);
    $this->assertEquals($profile->id, $this->user->profile->id);
}

/** @test */
public function it_has_players_scope()
{
    User::factory()->create(['role' => 'coach']);
    User::factory()->create(['role' => 'doctor']);
    
    $players = User::players()->get();
    
    $this->assertCount(2, $players);
    $this->assertTrue($players->every(fn($user) => $user->role === 'player'));
}
```

### 2. Service Tests

Test business logic and external integrations:

```php
/** @test */
public function it_classifies_player_with_valid_ai_response()
{
    Http::fake([
        'https://fastapi-predictorg-production.up.railway.app/predict' => Http::response([
            'Predicted Status' => 'Optimal',
            'Focus Area' => 'Strength Training',
            'Training Program' => ['Warm-up: 10 minutes']
        ], 200)
    ]);

    $result = $this->aiService->classifyPlayer($this->metrics);

    $this->assertEquals('Optimal', $result['status']);
    $this->assertEquals('Strength Training', $result['focus_area']);
}
```

### 3. Helper Tests

Test utility functions and formatting:

```php
/** @test */
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
```

### 4. Validation Tests

Test data validation and constraints:

```php
/** @test */
public function it_validates_fatigue_score_range()
{
    $this->expectException(\Illuminate\Database\QueryException::class);

    PlayerMetric::create([
        'player_id' => $this->player->id,
        'fatigue_score' => 150, // Should be 0-100
        'injury_risk' => 30,
        'readiness_score' => 65
    ]);
}
```

## Best Practices

### 1. Test Isolation

Each test should be independent and not rely on other tests:

```php
// Good - Each test creates its own data
/** @test */
public function it_can_create_user()
{
    $user = User::factory()->create();
    $this->assertDatabaseHas('users', ['id' => $user->id]);
}

/** @test */
public function it_can_update_user()
{
    $user = User::factory()->create(); // Fresh data
    $user->update(['name' => 'Updated']);
    $this->assertEquals('Updated', $user->fresh()->name);
}
```

### 2. Use Factories

Create test data using factories:

```php
// Good
$user = User::factory()->create(['role' => 'player']);
$metrics = PlayerMetric::factory()->count(5)->create(['player_id' => $user->id]);

// Avoid
$user = new User();
$user->name = 'Test User';
$user->email = 'test@example.com';
$user->save();
```

### 3. Mock External Dependencies

Mock external services and APIs:

```php
/** @test */
public function it_handles_api_failure_gracefully()
{
    Http::fake([
        'https://api.example.com/*' => Http::response([], 500)
    ]);

    $result = $this->service->callExternalApi();
    
    $this->assertNull($result);
}
```

### 4. Test Edge Cases

Test boundary conditions and error scenarios:

```php
/** @test */
public function it_handles_empty_values()
{
    $result = $this->service->analyzeMetric([], 'reaction_time');
    
    $this->assertEquals(['No metrics data available'], $result['highlights']);
}

/** @test */
public function it_handles_single_value()
{
    $result = $this->service->analyzeMetric([25], 'reaction_time');
    
    $this->assertEquals(25, $result['peak']['value']);
    $this->assertEquals(0, $result['trend']);
}
```

### 5. Use Descriptive Assertions

Make assertions clear and meaningful:

```php
// Good
$this->assertInstanceOf(Message::class, $message);
$this->assertEquals($sender->id, $message->sender_id);
$this->assertDatabaseHas('messages', ['sender_id' => $sender->id]);

// Avoid
$this->assertTrue($message instanceof Message);
$this->assertTrue($message->sender_id == $sender->id);
```

## Examples

### Complete Model Test Example

```php
<?php

namespace Tests\Unit;

use Tests\Unit\TestSuite;
use App\Models\User;
use App\Models\Message;
use App\Models\Notification;

class UserTest extends TestSuite
{
    protected User $user;
    protected User $player;
    protected User $coach;
    protected User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = $this->createUser('player', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $this->player = $this->createUser('player');
        $this->coach = $this->createUser('coach');
        $this->doctor = $this->createUser('doctor');
    }

    /** @test */
    public function it_can_create_a_user()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'player'
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'player'
        ]);
    }

    /** @test */
    public function it_has_relationships()
    {
        $message = $this->createMessage($this->user, $this->coach);
        $notification = $this->createNotification($this->user);

        $this->assertModelHasRelationships($this->user, [
            'sentMessages' => Message::class,
            'notifications' => Notification::class
        ]);
    }

    /** @test */
    public function it_can_send_message()
    {
        $recipient = $this->createUser();
        $messageData = [
            'type' => 'text',
            'content' => 'Hello, how are you?'
        ];

        $message = $this->user->sendMessage($recipient, $messageData);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($this->user->id, $message->sender_id);
        $this->assertEquals($recipient->id, $message->recipient_id);
        $this->assertEquals('text', $message->type);
        $this->assertEquals('Hello, how are you?', $message->content);
    }

    /** @test */
    public function it_has_role_scopes()
    {
        $this->createUser('coach');
        $this->createUser('doctor');

        $players = User::players()->get();
        $coaches = User::coaches()->get();
        $doctors = User::doctors()->get();

        $this->assertCount(2, $players); // Including $this->user and $this->player
        $this->assertCount(1, $coaches);
        $this->assertCount(1, $doctors);
        
        $this->assertTrue($players->every(fn($user) => $user->role === 'player'));
        $this->assertTrue($coaches->every(fn($user) => $user->role === 'coach'));
        $this->assertTrue($doctors->every(fn($user) => $user->role === 'doctor'));
    }
}
```

### Complete Service Test Example

```php
<?php

namespace Tests\Unit;

use Tests\Unit\TestSuite;
use App\Services\AIModelService;
use App\Models\User;
use App\Models\PlayerMetric;
use Illuminate\Support\Facades\Http;

class AIModelServiceTest extends TestSuite
{
    protected AIModelService $aiService;
    protected User $player;
    protected User $doctor;
    protected PlayerMetric $metrics;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->aiService = new AIModelService();
        $this->player = $this->createUser('player');
        $this->doctor = $this->createUser('doctor');
        
        $this->metrics = $this->createPlayerMetrics($this->player, [
            'fatigue_score' => 50,
            'injury_risk' => 30,
            'readiness_score' => 70
        ]);
    }

    /** @test */
    public function it_classifies_player_with_valid_ai_response()
    {
        Http::fake([
            'https://fastapi-predictorg-production.up.railway.app/predict' => Http::response([
                'Predicted Status' => 'Optimal',
                'Focus Area' => 'Strength Training',
                'Training Program' => [
                    'Warm-up: 10 minutes',
                    'Strength exercises: 45 minutes',
                    'Cool-down: 10 minutes'
                ]
            ], 200)
        ]);

        $result = $this->aiService->classifyPlayer($this->metrics);

        $this->assertEquals('Optimal', $result['status']);
        $this->assertEquals('Strength Training', $result['focus_area']);
        $this->assertIsArray($result['training_program']);
        $this->assertCount(3, $result['training_program']);
    }

    /** @test */
    public function it_returns_fallback_program_when_ai_api_fails()
    {
        Http::fake([
            'https://fastapi-predictorg-production.up.railway.app/predict' => Http::response([], 500)
        ]);

        $result = $this->aiService->classifyPlayer($this->metrics);

        $this->assertEquals('Optimal', $result['status']);
        $this->assertEquals('General Fitness', $result['focus_area']);
        $this->assertIsArray($result['training_program']);
    }

    /** @test */
    public function it_sends_correct_data_to_ai_api()
    {
        Http::fake([
            'https://fastapi-predictorg-production.up.railway.app/predict' => Http::response([
                'Predicted Status' => 'Optimal',
                'Focus Area' => 'Test',
                'Training Program' => ['Test']
            ], 200)
        ]);

        $this->aiService->classifyPlayer($this->metrics);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://fastapi-predictorg-production.up.railway.app/predict' &&
                   $request->method() === 'POST' &&
                   $request->data() === [
                       'fatigue_score' => 50,
                       'injury_risk' => 30,
                       'readiness_score' => 70
                   ];
        });
    }
}
```

## Running Tests in CI/CD

Add this to your CI/CD pipeline:

```yaml
# .github/workflows/tests.yml
name: Unit Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
        
    - name: Copy environment file
      run: cp .env.example .env
        
    - name: Generate application key
      run: php artisan key:generate
        
    - name: Create database
      run: |
        touch database/database.sqlite
        php artisan migrate --force
        
    - name: Run unit tests
      run: php artisan test --testsuite=Unit --coverage
```

## Conclusion

Unit testing is essential for maintaining code quality and preventing regressions. By following these guidelines, you'll create robust, maintainable tests that help ensure your application works correctly.

Remember:
- Write tests for all new features
- Keep tests simple and focused
- Use descriptive test names
- Mock external dependencies
- Test both happy path and edge cases
- Run tests frequently during development 