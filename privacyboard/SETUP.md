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

| Variable              | Required         | Example                    | Notes                                |
| --------------------- | ---------------- | -------------------------- | ------------------------------------ |
| `DB_TYPE`             | Yes              | mysql, pgsql, sqlite       | Database backend to use              |
| `DB_HOST`             | No (mysql/pgsql) | localhost                  | Host address                         |
| `DB_PORT`             | No               | 3306 (mysql), 5432 (pgsql) | Port number                          |
| `DB_NAME`             | No (mysql/pgsql) | privacyboard_db            | Database name                        |
| `DB_USER`             | No (mysql/pgsql) | root                       | Database user                        |
| `DB_PASS`             | No (mysql/pgsql) | password                   | Database password                    |
| `DB_PATH`             | No (sqlite)      | /tmp/privacyboard.db       | SQLite file path                     |
| `AUTH_TOKEN`          | No               | Bearer my-token            | Bearer token for authentication      |
| `CORS_ORIGIN`         | No               | \*                         | CORS origin (default: \*)            |
| `RATE_LIMIT_ENABLED`  | No               | true/false                 | Enable rate limiting (default: true) |
| `RATE_LIMIT_REQUESTS` | No               | 100                        | Max requests per window              |
| `RATE_LIMIT_WINDOW`   | No               | 60                         | Time window in seconds               |

## Security Best Practices

### 1. **Always Use HTTPS in Production**

Bearer tokens are sensitive—transmit only over HTTPS to prevent interception.

```bash
# Enforce HTTPS in your web server config (nginx example)
server {
    listen 443 ssl http2;
    ssl_certificate /path/to/cert.crt;
    ssl_certificate_key /path/to/key.key;
}
```

### 2. **Set a Strong Auth Token**

Use a cryptographically random token, not a predictable string.

```bash
# Generate a secure token
openssl rand -hex 32
# Output: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6

export AUTH_TOKEN="Bearer a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6"
```

### 3. **Restrict CORS Origins in Production**

Never use `CORS_ORIGIN=*` in production. Specify your frontend domain:

```bash
export CORS_ORIGIN=https://privacyboard.yourdomain.com
```

### 4. **Enable & Configure Rate Limiting**

Rate limiting prevents abuse and DDoS attacks. Default: 100 requests per 60 seconds.

For a public board with many users:

```bash
export RATE_LIMIT_REQUESTS=500
export RATE_LIMIT_WINDOW=60
```

To disable (not recommended):

```bash
export RATE_LIMIT_ENABLED=false
```

### 5. **Secure Database Connections**

- Use strong, unique database passwords
- For remote databases, enable SSL/TLS connections:
  - **MySQL**: Add `ssl=true` to DSN
  - **PostgreSQL**: Add `sslmode=require` to connection string
  - **SQLite**: Use file permissions (chmod 600)

```bash
# MySQL with SSL (requires PDO)
# In MySQLHandler: $dsn = "mysql:host=$host;port=$port;dbname=$name;ssl=true";
```

### 6. **Input Validation**

- Room IDs are sanitized to `[a-zA-Z0-9_-]` only
- JSON payloads are validated before saving
- All database queries use parameterized statements (SQL injection protected)

### 7. **Database User Permissions**

Use least-privilege database users—they should only have SELECT, INSERT, UPDATE on the `boards` table:

```sql
-- MySQL
GRANT SELECT, INSERT, UPDATE ON privacyboard_db.boards TO 'privacyboard'@'localhost';

-- PostgreSQL
GRANT SELECT, INSERT, UPDATE ON boards TO privacyboard;
```

### 8. **Log & Monitor**

- Enable PHP error logging (but keep it off display in production)
- Monitor 401 (auth failures) and 429 (rate limit) responses for abuse patterns
- SQLite users: Use file permissions to restrict database access

### 9. **Backup Strategy**

- Regular automated database backups
- Test restore procedures
- Store backups securely (encrypted, off-site)

### 10. **File Permissions**

Ensure appropriate file permissions on your server:

```bash
# SQLite database file
chmod 600 /path/to/privacyboard.db

# Config file containing secrets
chmod 600 /path/to/config.php

# API directory
chmod 755 /path/to/privacyboard/
```

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
