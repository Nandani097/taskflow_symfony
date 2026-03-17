# TaskFlow — Symfony Task Management System

A mini task management application built with **Symfony 7**, featuring task CRUD, comments, activity logs, Redis caching, and a JSON API.

## Tech Stack

- **Framework:** Symfony 7.4
- **Language:** PHP 8.x
- **Database:** MySQL 8.0
- **Cache:** Redis (via Predis)
- **Web Server:** Apache / Nginx
- **Frontend:** Twig + Bootstrap 5

## Setup Instructions

### 1. Prerequisites

```bash
sudo systemctl status apache2    # or nginx
php -v                           # PHP 8.x required
mysql --version                  # MySQL 8.0
composer --version               # Composer 2.x
redis-cli ping                   # Should return PONG
```

### 2. Clone & Install

```bash
git clone https://github.com/Nandani097/taskflow_symfony.git
cd taskflow_symfony
composer install
```

### 3. Database Setup

```bash
# Create database and import schema
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS taskflow_db;"
mysql -u root -p taskflow_db < database.sql

# Or use Symfony migrations
php bin/console doctrine:migrations:migrate
```

### 4. Configure Environment

Edit `.env` and set your database credentials:

```
DATABASE_URL="mysql://root:YOUR_PASSWORD@127.0.0.1:3306/taskflow_db?serverVersion=8.0&charset=utf8mb4"
REDIS_URL=redis://localhost:6379
```

### 5. Run the Application

```bash
symfony server:start
# OR
php -S localhost:8000 -t public/
```

Visit: `http://localhost:8000`

## Routes

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/home` | Home page |
| GET | `/login` | Login page |
| POST | `/login` | Login form submission |
| GET | `/logout` | Logout |
| GET | `/register` | Registration page |
| POST | `/register` | Register new user |
| GET | `/tasks` | Task list (filter, sort, search, pagination) |
| GET | `/task/new` | Create task form |
| POST | `/task/new` | Save new task |
| GET | `/task/{id}` | Task detail (comments + activity log) |
| GET | `/task/{id}/edit` | Edit task form |
| POST | `/task/{id}/edit` | Update task |
| POST | `/task/{id}/soft-delete` | Soft delete (read-only mode) |
| POST | `/task/{id}/delete` | Hard delete (permanent) |
| POST | `/task/{id}/comment` | Add comment |
| POST | `/comment/{id}/delete` | Delete comment |
| GET | `/api/tasks` | JSON API — all tasks with comment_count |

## Features

- **Authentication:** Session-based login/logout with registration
- **Task CRUD:** Create, read, update, soft delete, hard delete
- **Filtering:** Filter tasks by status (pending, in_progress, completed)
- **Sorting:** Sort by created date, priority, title, or status
- **Search:** Search tasks by title
- **Pagination:** Configurable items per page (5, 10, 25)
- **Comments:** Add/delete comments on tasks (one-to-many)
- **Activity Log:** Tracks task created, status changed, task deleted events
- **JSON API:** `GET /api/tasks` returns tasks with comment count
- **Redis Cache:** Caches task lists, comments, and activity logs
- **Validation:** Form validation with Symfony constraints

## Web Server Logs

```bash
# Nginx
tail -n 100 /var/log/nginx/error.log

# Symfony
tail -n 100 var/log/dev.log
```

## Dev Log

- **Day 1-2:** Set up Symfony project, created Task entity with CRUD, authentication (login/register), and base template with Bootstrap 5.
- **Day 3:** Added Comment entity with one-to-many relation, CommentService, and comment form on task detail page.
- **Day 4:** Implemented ActivityLog for audit trail (task created, status changed, deleted). Added soft delete with read-only mode.
- **Day 5:** Integrated Redis cache via CacheService for tasks, comments, and activity logs with automatic invalidation.
- **Day 6:** Added search, filter, sort, and pagination to task list. Created JSON API endpoint (`/api/tasks`). Generated `database.sql` and wrote README.
