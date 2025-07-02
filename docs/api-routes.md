# API Routes Documentation

## Auth Routes
- `POST /api/login`
- `POST /api/forgot-password`
- `POST /api/verify-reset-token`
- `POST /api/reset-password`
- `POST /api/logout` [Requires: auth:sanctum]

## Player Routes [Requires: auth:sanctum, role:player]
### Dashboard & Profile
- `GET /api/player/dashboard`
- `GET /api/player/profile`

### Metrics
- `GET /api/player/metrics`

### Assessment
- `POST /api/player/request-assessment`

### Training Program
- `GET /api/player/training-program`

### Notifications
- `GET /api/player/notifications`

## Coach Routes [Requires: auth:sanctum, role:coach]
### Dashboard & Profile
- `GET /api/coach/dashboard`
- `GET /api/coach/profile`

### Team Management
- `GET /api/coach/team/overview`
- `GET /api/coach/players/{player}/program`
- `GET /api/coach/players/{player}/metrics`
- `GET /api/coach/players/{player}/metrics/{metricType}`

## Doctor Routes [Requires: auth:sanctum, role:doctor]
### Dashboard & Profile
- `GET /api/doctor/dashboard`
- `GET /api/doctor/profile`

### Metrics
- `GET /api/doctor/metrics/{metricType}/details`

### Team & Players
- `GET /api/doctor/team/overview`
- `GET /api/doctor/players/{player}`

### AI Program Management
- `POST /api/doctor/players/{player}/ai-program`
- `POST /api/doctor/programs/{program}/approve`

### Assessment Management
- `GET /api/doctor/assessments`
- `POST /api/doctor/assessments/{assessment}/respond`

## Notification Routes [Requires: auth:sanctum]
- `GET /api/notifications`
- `GET /api/notifications/unread`
- `GET /api/notifications/pinned`
- `GET /api/notifications/unread/count`
- `GET /api/notifications/{id}`
- `POST /api/notifications/{id}/read`
- `POST /api/notifications/read-all`
- `POST /api/notifications/{notification}/pin`
- `POST /api/notifications/{notification}/unpin`
- `DELETE /api/notifications/{id}`

## Health Cards [Requires: auth:sanctum]
- `GET /api/health-cards/daily`

## Messaging System [Requires: auth:sanctum]
- `GET /api/conversations`
- `GET /api/messages/search`
- `GET /api/messages/{user}`
- `POST /api/messages/{recipient}`
- `POST /api/messages/{message}/read`
- `POST /api/messages/{message}/reaction`
- `DELETE /api/messages/{message}`
- `GET /api/messages/unread/count`