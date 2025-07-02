# API Documentation with Examples

## Authentication
### Login
```http
POST /api/login

Request:
{
    "email": "player@example.com",
    "password": "password123"
}
Response:
{
    "token": "2|4KLbDxHMZnrCBZ...",
    "user": {
        "id": 1,
        "email": "player@example.com",
        "role": "player"
    }
}
```

### Forgot Password
```http
POST /api/forgot-password

Request:
{
    "email": "player@example.com"

}
Response:   
{
    "message": "Password reset link sent to your email"
}
```
### Player Routes
#### Dashboard
```http
GET /api/player/dashboard  
Response:
{
    "metrics": {
        "recent_activities": [],
        "upcoming_sessions": [],
        "assessment_requests": []
    },
    "stats": {
        "completed_programs": 5,
        "active_programs": 1
    }
}
```
## Request Assessment
```http
POST /api/player/assessment/request

Request:
{
    "doctor_id": 1,
    "issue_type": "injury",
    "message": "Having knee pain after training"
}
Response:
{
    "id": 1,
    "status": "pending",
    "created_at": "2024-01-15T10:00:00Z"
}
```
### Doctor Routes
#### Get Assessments Requests
```http
GET /api/doctor/assessments
Response:
{
    "assessments": [
        {
            "id": 1,
            "player": {
                "id": 2,
                "name": "John Player"
            },
            "issue_type": "injury",
            "message": "Having knee pain after training",
            "status": "pending",
            "created_at": "2024-01-15T10:00:00Z"
        }
    ]
}
```
## Response To Assessment
```http
POST /api/doctor/assessments/{assessment_id}/responsed
Request:
{
    "response": "Schedule an appointment for detailed examination",
    "status": "approved"
}
Response:
{
    "message": "Assessment response sent successfully"
}
```

### Notification Routes
#### Get Notifications
```http
GET /api/notifications
Response:
{
    "notifications": [
        {
            "id": 1,
            "type": "assessment",
            "title": "Assessment Response",
            "body": "Doctor has responded to your assessment request",
            "read_at": null,
            "created_at": "2024-01-15T10:30:00Z"
        }
    ]
}
```
## Mark as Read
```http
POST /api/notifications/{notification_id}/read
Response:
{
    "message": "Notification marked as read"
}
```

### Message Routes
#### Get Conversations
```http
GET /api/messages/conversations
Response:
{
    "conversations": [
        {
            "id": 1,
            "participant": {
                "id": 2,
                "name": "John Doe",
                "username": "@johndoe",
                "avatar": "url/to/avatar"
            },
            "last_message": {
                "content": "Hello there",
                "created_at": "5m",
                "is_read": false
            },
            "unread_count": 1
        }
    ]
}
```
## Send Message
```http
POST /api/messages/conversations/{conversation_id}/send
Request:
{
    "type": "text",
    "content": "Hello there!"
}
Request (Photo/Voice Message):
Content-Type: multipart/form-data
{
    "type": "photo" or "voice",
    "file": [binary file data]
}
Response:
{
    "id": 1,
    "type": "text",
    "content": "Hello there!",
    "created_at": "2024-01-15T11:00:00Z",
    "sender": {
        "id": 1,
        "name": "Jane Doe",
        "avatar": "url/to/avatar"
    },
    "is_read": false
}