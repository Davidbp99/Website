<?php
/**
 * PostgreSQL Database Handler
 */

namespace PrivacyBoard\Database;

use PDO;
use PDOException;

class PostgreSQLHandler implements DatabaseInterface
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 5432;
        $name = $config['name'] ?? 'privacyboard_db';
        $user = $config['user'] ?? 'postgres';
        $pass = $config['pass'] ?? '';

        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$name";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            throw new \Exception('PostgreSQL connection failed: ' . $e->getMessage());
        }
    }

    public function initialize(): void
    {
        // PostgreSQL doesn't have native JSON columns until recently, use JSONB
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS boards (
                room_id VARCHAR(64) PRIMARY KEY,
                state JSONB NOT NULL,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create index for faster queries
        $this->pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_boards_last_updated
            ON boards(last_updated)
        ");
    }

    public function getBoard(string $roomId): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT state FROM boards WHERE room_id = ?
        ");
        $stmt->execute([$roomId]);
        $row = $stmt->fetch();

        return $row ? json_encode($row['state']) : null;
    }

    public function saveBoard(string $roomId, string $state): bool
    {
        // PostgreSQL upsert using ON CONFLICT
        $stmt = $this->pdo->prepare("
            INSERT INTO boards (room_id, state, last_updated)
            VALUES (?, ?::jsonb, CURRENT_TIMESTAMP)
            ON CONFLICT (room_id)
            DO UPDATE SET state = ?::jsonb, last_updated = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$roomId, $state, $state]);
    }

    public function deleteBoard(string $roomId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM boards WHERE room_id = ?");
        return $stmt->execute([$roomId]);
    }

    public function close(): void
    {
        $this->pdo = null;
    }
}
