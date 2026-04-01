<?php
/**
 * SQLite Database Handler
 */

namespace PrivacyBoard\Database;

use PDO;
use PDOException;

class SQLiteHandler implements DatabaseInterface
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $dbPath = $config['path'] ?? __DIR__ . '/privacyboard.db';

        try {
            $dsn = "sqlite:$dbPath";
            $this->pdo = new PDO($dsn, '', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Enable foreign keys and WAL mode for better concurrency
            $this->pdo->exec("PRAGMA foreign_keys = ON");
            $this->pdo->exec("PRAGMA journal_mode = WAL");
        } catch (PDOException $e) {
            throw new \Exception('SQLite connection failed: ' . $e->getMessage());
        }
    }

    public function initialize(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS boards (
                room_id TEXT PRIMARY KEY,
                state TEXT NOT NULL,
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

        return $row ? $row['state'] : null;
    }

    public function saveBoard(string $roomId, string $state): bool
    {
        // SQLite upsert using INSERT OR REPLACE
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO boards (room_id, state, last_updated)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$roomId, $state]);
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
