<?php
/**
 * Database Interface
 * 
 * Common interface all database handlers must implement
 */

namespace PrivacyBoard\Database;

interface DatabaseInterface
{
    /**
     * Initialize the database (create tables, etc.)
     */
    public function initialize(): void;

    /**
     * Get board state by room ID
     */
    public function getBoard(string $roomId): ?string;

    /**
     * Save or update board state
     */
    public function saveBoard(string $roomId, string $state): bool;

    /**
     * Delete a board (optional)
     */
    public function deleteBoard(string $roomId): bool;

    /**
     * Close connection
     */
    public function close(): void;
}
