<?php
/**
 * Database Factory
 * 
 * Provides a unified interface for multiple database types
 */

namespace PrivacyBoard\Database;

class DatabaseFactory
{
    /**
     * Create a database handler for the specified type
     */
    public static function create(string $type, array $config): DatabaseInterface
    {
        return match ($type) {
            'mysql' => new MySQLHandler($config),
            'pgsql' => new PostgreSQLHandler($config),
            'sqlite' => new SQLiteHandler($config),
            default => throw new \Exception("Unsupported database type: $type"),
        };
    }
}
