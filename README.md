# OptiKick API

A comprehensive Laravel-based API for sports team management and player performance tracking. This API provides role-based access control for players, coaches, doctors, and administrators with features including messaging, notifications, assessment requests, and AI-powered training programs.

## 🚀 Features

### 🔐 Authentication & Authorization
- **Laravel Sanctum** for API authentication
- **Role-based access control** (Player, Coach, Doctor, Admin)
- **Rate limiting** for security (6 attempts per minute for auth routes)
- **Password reset** functionality

### 👥 User Management
- **Multi-role system**: Players, Coaches, Doctors, and Administrators
- **User profiles** with detailed information
- **Team management** and player assignments

### 📊 Performance Tracking
- **Player metrics** tracking and analysis
- **Training programs** management
- **Assessment requests** system
- **AI-powered program generation** for players

### 💬 Communication
- **Real-time messaging** system with conversations
- **Message reactions** and read status
- **File sharing** (photos, voice messages)
- **Push notifications** support

### 🔔 Notifications
- **Comprehensive notification system**
- **Unread count tracking**
- **Pinned notifications**
- **Bulk operations** (mark all as read)

### 🏥 Health Management
- **Assessment scheduling**
- **Doctor-player communication**

## 🛠️ Technology Stack

- **Framework**: Laravel 12.x
- **Authentication**: Laravel Sanctum
- **Database**: MySQL
- **Admin Panel**: Filament 3.x
- **Real-time**: Pusher integration
- **Testing**: PHPUnit
- **Code Quality**: Laravel Pint

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- Node.js & NPM (for frontend assets)
- MySQL

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/optikick.git
cd optikick
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup
```bash
Configure MySQL/PostgreSQL in .env file
```

### 5. Run Migrations
```bash
php artisan migrate
```

### 6. Start Development Server
```bash
php artisan serve

```

## 🔧 Configuration

### Environment Variables
Create a `.env` file with the following key configurations:

```env
APP_NAME=OptiKick
APP_ENV=local
APP_KEY=base64:your-key-here
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=optikick
DB_USERNAME=your_username
DB_PASSWORD=your_password

PUSHER_APP_ID=your-pusher-app-id
PUSHER_APP_KEY=your-pusher-key
PUSHER_APP_SECRET=your-pusher-secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1
```


## 🧪 Testing

OptiKick includes a comprehensive testing suite to ensure code quality and reliability. The project supports multiple types of testing to cover different aspects of the application.

### 📋 Test Types

#### 1. **Unit Tests** (`tests/Unit/`)
Test individual components in isolation:
- **Models**: Database relationships, scopes, accessors, mutators
- **Services**: Business logic, external API integrations
- **Helpers**: Utility functions and formatting logic
- **Policies**: Authorization logic

#### 2. **Feature Tests** (`tests/Feature/`)
Test how multiple components work together:
- **API endpoints**: HTTP requests and responses
- **Authentication flows**: Login, logout, password reset
- **Authorization**: Role-based access control
- **Database operations**: CRUD operations with relationships

#### 3. **Performance Tests** (`tests/Performance/`)
Test application performance and load handling:
- **Endpoint performance**: Response times and throughput
- **Load testing**: Concurrent user simulation
- **Stress testing**: Breaking point identification
- **Memory usage**: Resource consumption monitoring

### 🚀 Running Tests

#### Quick Start
```bash
# Run all tests
php artisan test

# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Unit/UserTest.php

# Run specific test method
php artisan test --filter test_can_create_user
```

#### Advanced Testing Options
```bash
# Run tests with coverage report
php artisan test --coverage

# Run tests in parallel (faster execution)
php artisan test --parallel

# Run tests with verbose output
php artisan test --verbose

# Stop on first failure
php artisan test --stop-on-failure
```

#### Using the Test Runner Script
```bash
# Make script executable (Linux/Mac)
chmod +x scripts/run-tests.sh

# Run different test types
./scripts/run-tests.sh unit
./scripts/run-tests.sh feature
./scripts/run-tests.sh all
./scripts/run-tests.sh coverage --coverage-html
./scripts/run-tests.sh performance
```

### 📊 Test Coverage

The project aims for high test coverage across all components:

- **Models**: 90%+ coverage
- **Services**: 95%+ coverage
- **Controllers**: 85%+ coverage
- **Helpers**: 100% coverage

### 🏗️ Test Structure

```
tests/
├── Unit/                          # Unit tests
│   ├── TestSuite.php             # Base test class with helpers
│   ├── UserTest.php              # User model tests
│   ├── PlayerMetricTest.php      # PlayerMetric model tests
│   ├── AIModelServiceTest.php    # AI service tests
│   ├── MetricAnalysisServiceTest.php # Metric analysis tests
│   ├── MessageHelperTest.php     # Message helper tests
│   └── NotificationFormatterTest.php # Notification tests
├── Feature/                       # Feature tests (integration)
│   ├── Auth/                     # Authentication tests
│   ├── API/                      # API endpoint tests
│   └── Database/                 # Database operation tests
├── Performance/                   # Performance tests
│   └── EndpointPerformanceTest.php
└── TestCase.php                  # Base test case
```

### 📝 Writing Tests

#### Unit Test Example
```php
/** @test */
public function it_can_create_a_user()
{
    // Arrange - Set up test data
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password'),
        'role' => 'player'
    ];

    // Act - Execute the method being tested
    $user = User::create($userData);

    // Assert - Verify the results
    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'player'
    ]);
}
```

#### Feature Test Example
```php
/** @test */
public function user_can_login_with_valid_credentials()
{
    // Arrange
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password')
    ]);

    // Act
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password'
    ]);

    // Assert
    $response->assertStatus(200)
             ->assertJsonStructure(['token', 'user']);
}
```

### 🔧 Test Configuration

#### Environment Setup
Tests use a separate database configuration:

```env
# Testing environment
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

#### Test Data Factories
The project includes comprehensive factories for generating test data:

```php
// Create a user with specific role
$player = User::factory()->create(['role' => 'player']);

// Create multiple related records
$metrics = PlayerMetric::factory()->count(5)->create([
    'player_id' => $player->id
]);
```

### 📈 Performance Testing

#### Load Testing with K6
```bash
# Install K6 (if not already installed)
# macOS: brew install k6
# Windows: choco install k6

# Run load test
k6 run k6/load-test.js

# Run stress test
k6 run k6/stress-test.js
```

#### Performance Test Commands
```bash
# Run performance tests via Artisan
php artisan test:performance --type=all
php artisan test:performance --type=endpoint --endpoint=api/login
php artisan test:performance --type=load --concurrent=100 --duration=300
```

### 🎯 Best Practices

1. **Test Isolation**: Each test should be independent
2. **Descriptive Names**: Use clear, descriptive test method names
3. **AAA Pattern**: Follow Arrange-Act-Assert structure
4. **Mock External Dependencies**: Mock APIs and external services
5. **Test Edge Cases**: Include boundary conditions and error scenarios
6. **Use Factories**: Generate test data using Laravel factories
7. **Run Tests Frequently**: Execute tests during development

### 📚 Additional Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [K6 Load Testing Guide](https://k6.io/docs/)
- [Project Testing Guide](docs/unit-testing.md)

## 📁 Project Structure

```
optikick/
├── app/
│   ├── Http/Controllers/API/     # API Controllers
│   ├── Models/                   # Eloquent Models
│   ├── Services/                 # Business Logic
│   └── Events/                   # Event Classes
├── database/
│   ├── migrations/               # Database Migrations
│   ├── seeders/                  # Database Seeders
│   └── factories/                # Model Factories
├── routes/
│   └── api.php                   # API Routes
├── tests/
│   ├── Unit/                     # Unit tests
│   ├── Feature/                  # Feature tests
│   └── Performance/              # Performance tests
├── k6/                          # K6 load testing scripts
├── scripts/                     # Test runner scripts
└── docs/                        # Documentation
    └── unit-testing.md          # Detailed testing guide
```


## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Check the [API Documentation](https://documenter.getpostman.com/view/33505324/2sB34ZrjUh#intro)

## 🔄 Version History

- **v1.0.0** - Initial release with core features
- Role-based authentication
- Messaging system
- Notification system
- Player metrics tracking
- Assessment request system

---
