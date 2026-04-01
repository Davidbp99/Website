<?php
/**
 * Authentication Handler
 * 
 * Handles Bearer token authentication across all database types
 * Auth is DATABASE-AGNOSTIC — it just validates HTTP headers
 */

namespace PrivacyBoard\Auth;

class AuthHandler
{
    private string $expectedToken;

    public function __construct(string $token = '')
    {
        $this->expectedToken = trim($token);
    }

    /**
     * Check if request is authenticated
     * Returns true if no token is set (auth disabled) or if token matches
     * Returns false and exits with 401 if token is required but invalid
     */
    public function authenticate(): bool
    {
        // If no token is set, auth is disabled
        if ($this->expectedToken === '') {
            return true;
        }

        $providedToken = $this->getAuthorizationHeaderValue();

        if ($providedToken === '' || !hash_equals($this->expectedToken, $providedToken)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        return true;
    }

    /**
     * Extract Authorization header from various server configurations
     * Works with Apache, Nginx, FastCGI, etc.
     */
    private function getAuthorizationHeaderValue(): string
    {
        $token = '';

        // Method 1: getallheaders() (Apache, some Nginx setups)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            foreach (['Authorization', 'authorization'] as $key) {
                if (isset($headers[$key])) {
                    $token = $headers[$key];
                    break;
                }
            }
        }

        // Method 2: $_SERVER fallbacks (FastCGI, Nginx)
        if ($token === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $token = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if ($token === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $token = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return trim($token);
    }

    /**
     * Set CORS headers (authentication-independent)
     */
    public static function setCorsHeaders(string $origin = '*'): void
    {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
    }

    /**
     * Handle preflight CORS requests
     */
    public static function handleCorsPreFlight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
