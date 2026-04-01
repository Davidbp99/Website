# PrivacyBoard Multi-Database Setup

## Quick Start

### 1. MySQL Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE privacyboard_db;"

# Set environment variables (Linux/Mac):
export DB_TYPE=mysql
export DB_HOST=127.0.0.1
export DB_USER=root
export DB_PASS=your_password
export AUTH_TOKEN="Bearer my-secret-token"

# Or create .env file and use a library like vlucas/phpdotenv
```

### 2. PostgreSQL Setup

```bash
# Create database
createdb privacyboard_db

# Set environment variables:
export DB_TYPE=pgsql
export DB_HOST=localhost
export DB_USER=postgres
export DB_PASS=your_password
export AUTH_TOKEN="Bearer my-secret-token"
```

### 3. SQLite Setup (Easiest for Development)

```bash
# SQLite creates the database automatically
export DB_TYPE=sqlite
export DB_PATH=/tmp/privacyboard.db
export AUTH_TOKEN="Bearer my-secret-token"
```

## Environment Variables

| Variable      | Required         | Example                    | Notes                           |
| ------------- | ---------------- | -------------------------- | ------------------------------- |
| `DB_TYPE`     | Yes              | mysql, pgsql, sqlite       | Database backend to use         |
| `DB_HOST`     | No (mysql/pgsql) | localhost                  | Host address                    |
| `DB_PORT`     | No               | 3306 (mysql), 5432 (pgsql) | Port number                     |
| `DB_NAME`     | No (mysql/pgsql) | privacyboard_db            | Database name                   |
| `DB_USER`     | No (mysql/pgsql) | root                       | Database user                   |
| `DB_PASS`     | No (mysql/pgsql) | password                   | Database password               |
| `DB_PATH`     | No (sqlite)      | /tmp/privacyboard.db       | SQLite file path                |
| `AUTH_TOKEN`  | No               | Bearer my-token            | Bearer token for authentication |
| `CORS_ORIGIN` | No               | \*                         | CORS origin (default: \*)       |

## How Authentication Works

**Authentication is DATABASE-AGNOSTIC** — it only validates HTTP headers.

All requests must include the `Authorization` header:

```
Authorization: Bearer my-secret-token
```

The `AuthHandler` class extracts this header reliably across different server configurations (Apache, Nginx, FastCGI, etc.).

## API Endpoints

### Get Board State

```bash
curl -H "Authorization: Bearer my-secret-token" \
  "https://yourdomain.com/board_api.php?room=my-board-id"
```

### Save Board State

```bash
curl -X POST \
  -H "Authorization: Bearer my-secret-token" \
  -H "Content-Type: application/json" \
  -d '{"columns": [...]}' \
  "https://yourdomain.com/board_api.php?room=my-board-id"
```

## Database Comparison

| Feature          | MySQL      | PostgreSQL | SQLite                |
| ---------------- | ---------- | ---------- | --------------------- |
| JSON Support     | ✓          | ✓ (JSONB)  | ✓ (TEXT)              |
| Scalability      | Excellent  | Excellent  | Good (single machine) |
| Setup Complexity | Medium     | Medium     | Easy                  |
| Best For         | Production | Production | Development/Small     |
| Concurrent Users | 1000+      | 1000+      | <100                  |
| Persistence      | Disk       | Disk       | Disk                  |

## Switching Databases

To switch from MySQL to PostgreSQL:

1. Export your MySQL data:

```bash
mysqldump -u root -p privacyboard_db > backup.sql
```

2. Create new PostgreSQL database and restore:

```bash
createdb privacyboard_db
# Adapt SQL syntax as needed
```

3. Update environment variables:

```bash
export DB_TYPE=pgsql
export DB_HOST=localhost
export DB_USER=postgres
```

4. **No code changes needed** — the API automatically uses the PostgreSQL handler!

## Adding New Database Types

To add support for a new database (e.g., MongoDB):

1. Create `db/MongoDBHandler.php` implementing `DatabaseInterface`
2. Add case to `DatabaseFactory::create()`
3. Add config handling in `config.php`
4. Update environment variables in docs

Example:

```php
// db/MongoDBHandler.php
class MongoDBHandler implements DatabaseInterface {
    public function initialize(): void { /* ... */ }
    public function getBoard(string $roomId): ?string { /* ... */ }
    public function saveBoard(string $roomId, string $state): bool { /* ... */ }
    // ...
}
```

## Troubleshooting

### "Unauthorized" error

- Check `AUTH_TOKEN` matches the header value
- Verify `Authorization: Bearer` format

### Database connection failed

- Verify credentials in environment variables
- Check host/port are correct
- Ensure database exists

### Table not found

- `board_api.php` auto-creates tables on first run
- Check database user has CREATE TABLE permissions

### CORS errors

- Set `CORS_ORIGIN` environment variable or in PrivacyBoard settings
- For development, default `*` allows all origins

## PHP Configuration Notes

- Requires PDO extensions: `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`
- Must support `php://input` stream (standard)
- Tested with PHP 7.4+
