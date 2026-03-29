<?php
/**
 * PrivacyBoard MySQL API Example
 * 
 * This script acts as the "middleman" between PrivacyBoard's frontend and your MySQL database.
 * To use this:
 * 1. Create a MySQL database and update the credentials below.
 * 2. Host this script on your web server (e.g. https://yourdomain.com/board_api.php).
 * 3. In PrivacyBoard's Storage Settings, select "MySQL / REST API" and enter your URL.
 */

// ── CONFIGURATION ── //
$DB_HOST = '127.0.0.1';
$DB_NAME = 'privacyboard_db';
$DB_USER = 'root';
$DB_PASS = 'your_mysql_password';

// Optional: Set a secret token to prevent unauthorized people from reading/writing
// Matches the "Auth Token" field in PrivacyBoard Storage Config
$AUTH_TOKEN = 'Bearer my-secret-token'; 


// ── CORS HEADERS ── //
// Allows PrivacyBoard (hosted anywhere) to communicate with this script
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Answer preflight OPTIONS request immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// ── AUTHENTICATION ── //
if ($AUTH_TOKEN !== '') {
    $headers = getallheaders();
    $providedToken = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    if ($providedToken !== $AUTH_TOKEN) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

// ── DATABASE CONNECTION ── //
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Create table if it doesn't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS boards (
        room_id VARCHAR(64) PRIMARY KEY,
        state JSON NOT NULL,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// ── HANDLE REQUESTS ── //
$roomId = isset($_GET['room']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['room']) : '';

if (!$roomId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing room ID']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // LOAD BOARD DATA
    $stmt = $pdo->prepare("SELECT state FROM boards WHERE room_id = ?");
    $stmt->execute([$roomId]);
    $row = $stmt->fetch();

    if ($row) {
        echo $row['state']; // Already JSON
    } else {
        // Return absolutely nothing if room doesn't exist (simulates Firebase empty state)
        echo json_encode(null);
    }
} 
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SAVE BOARD DATA
    $jsonContent = file_get_contents('php://input');
    
    // Ensure it's valid JSON
    if (json_decode($jsonContent) === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO boards (room_id, state) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE state = ?
    ");
    $stmt->execute([$roomId, $jsonContent, $jsonContent]);

    echo json_encode(['success' => true]);
} 
else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
