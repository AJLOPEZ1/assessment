# Project Management API

A comprehensive project management API built with Laravel, featuring role-based authentication, task management, and real-time notifications.

## Features

- **Authentication & Authorization**: JWT-based authentication with role-based access control
- **Project Management**: Full CRUD operations for projects (Admin only)
- **Task Management**: Task creation, assignment, and status tracking
- **Comments System**: Task commenting functionality
- **Email Notifications**: Automated notifications for task assignments
- **Caching**: Redis-based caching for improved performance
- **Queue Processing**: Background job processing for notifications
- **API Logging**: Comprehensive API request logging
- **Testing**: Feature and unit tests with 85%+ coverage

## Tech Stack

- **Backend**: Laravel 10.x
- **Authentication**: Laravel Sanctum
- **Database**: MySQL/SQLite
- **Queue**: Database driver
- **Cache**: File/Redis
- **Testing**: PHPUnit
- **Email**: SMTP (configurable)

## Setup Instructions

### Prerequisites

- PHP 8.1+
- Composer
- MySQL or SQLite
- Node.js & npm (optional, for frontend assets)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd assessment
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   - For SQLite (recommended for development):
     ```bash
     touch database/database.sqlite
     ```
     Update `.env`:
     ```
     DB_CONNECTION=sqlite
     DB_DATABASE=/absolute/path/to/database/database.sqlite
     ```
   
   - For MySQL:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=assessment
     DB_USERNAME=root
     DB_PASSWORD=your_password
     ```

5. **Run migrations and seed data**
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Create queue table and run worker**
   ```bash
   php artisan queue:table
   php artisan migrate
   php artisan queue:work --daemon
   ```

7. **Start the server**
   ```bash
   php artisan serve
   ```

## Seeded Data

The application comes with pre-seeded test data:

### Users
- **3 Admins**: Full access to project management
- **3 Managers**: Can manage tasks and assignments
- **5 Regular Users**: Can comment and work on assigned tasks

### Projects
- **5 Sample Projects**: Created by admin users

### Tasks
- **10 Sample Tasks**: Distributed across projects and assigned to users

### Comments
- **10 Sample Comments**: Added to various tasks by different users

## Role-Based Access Control

### API Documentation

https://doba-dot.postman.co/workspace/My-Workspace~7f9804e3-714a-46cb-8228-8b98ccb3e966/collection/12873571-6030fd6e-cff0-4560-a01e-714b4ee3b8a2?action=share&creator=12873571

### Admin
- Full project management (CRUD)
- Can view all data
- Cannot be assigned to tasks

### Manager
- Can create, update, delete tasks
- Can assign tasks to users
- Can view all projects and tasks
- Can comment on tasks

### User
- Can view assigned tasks and projects
- Can update status of assigned tasks
- Can comment on tasks
- Cannot create or delete tasks/projects

## Architecture & Services

### TaskAssignmentService
- Validates user eligibility for task assignment
- Prevents admin users from being assigned tasks
- Limits active tasks per user (max 10)
- Provides task statistics per user

### CommonQueryScopes Trait
- `filterByStatus()`: Filter records by status
- `searchByTitle()`: Search records by title
- `dateRange()`: Filter by date range
- `recent()`: Get recent records

### Middleware
- **LogApiRequests**: Logs all API requests with user ID, endpoint, and timestamp
- **CheckRole**: Validates user roles for protected routes

### Notifications
- **TaskAssignedNotification**: Queued email notification sent when tasks are assigned
- Includes task details, project information, and due date

### Caching
- Project listings are cached for 1 hour
- Cache is automatically cleared when projects are modified
- Configurable cache drivers (file, redis, etc.)

## Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run specific test files
php artisan test tests/Feature/AuthTest.php
php artisan test tests/Feature/ProjectTest.php
php artisan test tests/Unit/TaskAssignmentServiceTest.php

# Run tests with coverage
php artisan test --coverage
```

### Test Coverage
- **Feature Tests**: Authentication, Project CRUD, Task management, Comments
- **Unit Tests**: TaskAssignmentService functionality
- **Coverage**: 85%+ across controllers and services

## Queue Management

### Start Queue Worker
```bash
php artisan queue:work
```

### Monitor Failed Jobs
```bash
php artisan queue:failed
```

### Retry Failed Jobs
```bash
php artisan queue:retry all
```

## Troubleshooting

### Database Issues
- Ensure database connection is properly configured in `.env`
- Run `php artisan migrate:fresh --seed` to reset database

### Permission Issues
- Check file permissions for `storage/` and `bootstrap/cache/`
- Run `php artisan cache:clear` and `php artisan config:clear`

### Queue Not Processing
- Ensure `QUEUE_CONNECTION=database` in `.env`
- Start queue worker: `php artisan queue:work`
- Check failed jobs: `php artisan queue:failed`

## Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request
