<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Secure PDO Database Connection (Singleton)
 * Prevents SQL injection through prepared statements only
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct()
    {
        try {
            $dsn = 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4';

            $this->pdo = new PDO(
                $dsn,
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,  // Use native prepared statements
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Database connection failed. Please contact support.');
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute prepared query
     * @param string $query SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return \PDOStatement
     */
    public function prepare(string $query): \PDOStatement
    {
        return $this->pdo->prepare($query);
    }

    /**
     * Execute query and return results
     * @param string $query SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return array Results as associative array
     */
    public function select(string $query, array $params = []): array
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute query and return single row
     * @param string $query SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return array|false Single row or false if not found
     */
    public function selectOne(string $query, array $params = []): array|false
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Insert record and return last insert ID
     * @param string $query INSERT query with ? placeholders
     * @param array $params Parameters to bind
     * @return string Last insert ID
     */
    public function insert(string $query, array $params = []): string
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update or delete records
     * @param string $query UPDATE/DELETE query with ? placeholders
     * @param array $params Parameters to bind
     * @return int Rows affected
     */
    public function update(string $query, array $params = []): int
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Delete records (alias for update)
     * @param string $query DELETE query with ? placeholders
     * @param array $params Parameters to bind
     * @return bool True if rows were deleted
     */
    public function delete(string $query, array $params = []): bool
    {
        return $this->update($query, $params) > 0;
    }

    /**
     * Check if currently in a transaction
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Begin transaction
     * @throws \RuntimeException if transaction fails to start
     */
    public function beginTransaction(): void
    {
        // Avoid nested transactions
        if ($this->pdo->inTransaction()) {
            throw new \RuntimeException('Transaction already in progress');
        }

        if (!$this->pdo->beginTransaction()) {
            throw new \RuntimeException('Failed to start database transaction');
        }
    }

    /**
     * Commit transaction
     * @throws \RuntimeException if commit fails
     */
    public function commit(): void
    {
        if (!$this->pdo->inTransaction()) {
            throw new \RuntimeException('No active transaction to commit');
        }

        if (!$this->pdo->commit()) {
            throw new \RuntimeException('Failed to commit transaction');
        }
    }

    /**
     * Rollback transaction (safe - won't throw if no active transaction)
     */
    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent serialization
    public function __sleep()
    {
        throw new \Exception('Cannot serialize singleton');
    }
}
