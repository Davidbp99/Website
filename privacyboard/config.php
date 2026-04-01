<?php
/**
 * PrivacyBoard Configuration
 * 
 * Supported databases: mysql, pgsql, sqlite
 */

// Database configuration
$DB_TYPE = getenv('DB_TYPE') ?: 'mysql'; // mysql, pgsql, sqlite

// Host-based config (override with environment variables)
switch ($DB_TYPE) {
    case 'mysql':
        $DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
        $DB_PORT = getenv('DB_PORT') ?: 3306;
        $DB_NAME = getenv('DB_NAME') ?: 'privacyboard_db';
        $DB_USER = getenv('DB_USER') ?: 'root';
        $DB_PASS = getenv('DB_PASS') ?: 'your_mysql_password';
        break;

    case 'pgsql':
        $DB_HOST = getenv('DB_HOST') ?: 'localhost';
        $DB_PORT = getenv('DB_PORT') ?: 5432;
        $DB_NAME = getenv('DB_NAME') ?: 'privacyboard_db';
        $DB_USER = getenv('DB_USER') ?: 'postgres';
        $DB_PASS = getenv('DB_PASS') ?: 'your_postgres_password';
        break;

    case 'sqlite':
        // For SQLite, DB_NAME is the file path
        $DB_PATH = getenv('DB_PATH') ?: __DIR__ . '/privacyboard.db';
        break;

    default:
        throw new Exception("Unsupported database type: $DB_TYPE");
}

// Authentication Token
// Set a secret token to prevent unauthorized access
// If empty, authentication is disabled
$AUTH_TOKEN = getenv('AUTH_TOKEN') ?: 'Bearer my-secret-token';

// CORS configuration
$CORS_ORIGIN = getenv('CORS_ORIGIN') ?: '*';
