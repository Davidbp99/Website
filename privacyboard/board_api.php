<?php
/**
 * PrivacyBoard Multi-Database API
 * 
 * Unified API endpoint supporting MySQL, PostgreSQL, and SQLite
 * All database types support the same Authentication mechanism (Bearer tokens)
 * 
 * SETUP:
 * 1. Copy this file to your web server
 * 2. Set environment variables or edit config.php:
 *    - DB_TYPE: mysql, pgsql, or sqlite
 *    - DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS (for mysql/pgsql)
 *    - DB_PATH (for sqlite, defaults to ./privacyboard.db)
 *    - AUTH_TOKEN: optional Bearer token for security
 * 3. In PrivacyBoard Settings, use the URL of this script
 * 4. Add Authorization header with your token if set
 */

// Autoloader for our classes
spl_autoload_register(function ($class) {
    $prefix = 'PrivacyBoard\\';
    if (strpos($class, $prefix) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = __DIR__ . '/db/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Load configuration
require_once __DIR__ . '/config.php';

use PrivacyBoard\Database\DatabaseFactory;
use PrivacyBoard\Auth\AuthHandler;

// ── CORS & PREFLIGHT ── //
AuthHandler::setCorsHeaders($CORS_ORIGIN);
AuthHandler::handleCorsPreFlight();

header('Content-Type: application/json; charset=utf-8');

try {
    // ── AUTHENTICATION ── //
    $auth = new AuthHandler($AUTH_TOKEN);
    $auth->authenticate();

    // ── DATABASE CONNECTION ── //
    $config = [
        'type' => $DB_TYPE
    ];

    switch ($DB_TYPE) {
        case 'mysql':
            $config += [
                'host' => $DB_HOST,
                'port' => $DB_PORT,
                'name' => $DB_NAME,
                'user' => $DB_USER,
                'pass' => $DB_PASS
            ];
            break;

        case 'pgsql':
            $config += [
                'host' => $DB_HOST,
                'port' => $DB_PORT,
                'name' => $DB_NAME,
                'user' => $DB_USER,
                'pass' => $DB_PASS
            ];
            break;

        case 'sqlite':
            $config += [
                'path' => $DB_PATH
            ];
            break;
    }

    $db = DatabaseFactory::create($DB_TYPE, $config);
    $db->initialize(); // Create tables if needed

    // ── HANDLE REQUESTS ── //
    $roomId = isset($_GET['room']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['room']) : '';

    if (!$roomId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing room ID']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // ── LOAD BOARD DATA ── //
        $state = $db->getBoard($roomId);
        echo $state ?? json_encode(null);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ── SAVE BOARD DATA ── //
        $jsonContent = file_get_contents('php://input');

        // Validate JSON
        json_decode($jsonContent);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }

        if ($db->saveBoard($roomId, $jsonContent)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save board']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

    $db->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
