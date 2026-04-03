<?php
/**
 * Rate Limiting Handler
 * 
 * Prevents abuse by limiting requests per token/IP
 * Uses simple file-based storage (no Redis required)
 */

namespace PrivacyBoard\Auth;

class RateLimiter
{
    private string $storePath;
    private int $maxRequests;
    private int $windowSeconds;
    private array $data = [];

    /**
     * @param string $storePath Path to rate limit data file
     * @param int $maxRequests Max requests allowed per window
     * @param int $windowSeconds Time window in seconds (default: 60 = 1 minute)
     */
    public function __construct(
        string $storePath = '',
        int $maxRequests = 100,
        int $windowSeconds = 60
    ) {
        $this->storePath = $storePath ?: sys_get_temp_dir() . '/privacyboard_ratelimit.json';
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->load();
    }

    /**
     * Check if request should be allowed
     * Returns true if within limits, false if rate limit exceeded
     */
    public function isAllowed(string $identifier): bool
    {
        $now = time();
        $key = $identifier;

        // Initialize if not exists
        if (!isset($this->data[$key])) {
            $this->data[$key] = [];
        }

        // Remove old requests outside the window
        $this->data[$key] = array_filter(
            $this->data[$key],
            fn($timestamp) => ($now - $timestamp) < $this->windowSeconds
        );

        // Check if within limit
        if (count($this->data[$key]) >= $this->maxRequests) {
            return false;
        }

        // Add current request
        $this->data[$key][] = $now;
        $this->save();

        return true;
    }

    /**
     * Load rate limit data from file
     */
    private function load(): void
    {
        if (!file_exists($this->storePath)) {
            return;
        }

        try {
            $contents = file_get_contents($this->storePath);
            $data = json_decode($contents, true);
            if (is_array($data)) {
                $this->data = $data;
            }
        } catch (\Exception $e) {
            // Silently fail, start fresh
        }
    }

    /**
     * Save rate limit data to file
     */
    private function save(): void
    {
        try {
            // Clean up old entries to prevent file from growing too large
            $now = time();
            foreach ($this->data as &$requests) {
                $requests = array_filter(
                    $requests,
                    fn($timestamp) => ($now - $timestamp) < $this->windowSeconds
                );
            }
            unset($requests);

            // Only keep entries with actual requests
            $this->data = array_filter($this->data, fn($requests) => !empty($requests));

            file_put_contents(
                $this->storePath,
                json_encode($this->data),
                LOCK_EX
            );
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the API
        }
    }

    /**
     * Get request count for identifier
     */
    public function getCount(string $identifier): int
    {
        $now = time();
        if (!isset($this->data[$identifier])) {
            return 0;
        }

        $requests = array_filter(
            $this->data[$identifier],
            fn($timestamp) => ($now - $timestamp) < $this->windowSeconds
        );

        return count($requests);
    }
}
