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
