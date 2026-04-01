<?php
/**
 * MySQL Database Handler
 */

namespace PrivacyBoard\Database;

use PDO;
use PDOException;

class MySQLHandler implements DatabaseInterface
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $name = $config['name'] ?? 'privacyboard_db';
        $user = $config['user'] ?? 'root';
        $pass = $config['pass'] ?? '';

        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            throw new \Exception('MySQL connection failed: ' . $e->getMessage());
        }
    }

    public function initialize(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS boards (
                room_id VARCHAR(64) PRIMARY KEY,
                state JSON NOT NULL,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function getBoard(string $roomId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT state FROM boards WHERE room_id = ?");
        $stmt->execute([$roomId]);
        $row = $stmt->fetch();

        return $row ? $row['state'] : null;
    }

    public function saveBoard(string $roomId, string $state): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO boards (room_id, state)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE state = VALUES(state)
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
