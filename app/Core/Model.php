<?php

namespace App\Core;

/**
 * Base Model class with common database operations
 */
abstract class Model
{
    protected Database $db;
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find record by ID
     */
    public function find(int $id): array|false
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Find by specific field
     */
    public function findBy(string $field, $value): array|false
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE {$field} = ?",
            [$value]
        );
    }

    /**
     * Get all records
     */
    public function getAll(string $orderBy = 'id DESC', ?int $limit = null): array
    {
        $query = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        if ($limit) {
            $query .= " LIMIT {$limit}";
        }
        return $this->db->select($query);
    }

    /**
     * Get records with pagination
     */
    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM {$this->table} LIMIT ? OFFSET ?";
        return $this->db->select($query, [$perPage, $offset]);
    }

    /**
     * Count total records
     */
    public function count(): int
    {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM {$this->table}");
        return (int)($result['count'] ?? 0);
    }

    /**
     * Create record
     */
    public function create(array $data): int|string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        return $this->db->insert($query, array_values($data));
    }

    /**
     * Update record by ID
     */
    public function update(int $id, array $data): int
    {
        $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        $query = "UPDATE {$this->table} SET {$set} WHERE id = ?";
        return $this->db->update($query, $values);
    }

    /**
     * Delete record by ID
     */
    public function delete(int $id): int
    {
        return $this->db->update("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Execute raw query
     */
    public function query(string $query, array $params = []): array
    {
        return $this->db->select($query, $params);
    }

    /**
     * Execute raw query for single result
     */
    public function queryOne(string $query, array $params = []): array|false
    {
        return $this->db->selectOne($query, $params);
    }

    /**
     * Get database instance
     */
    public function getDb(): Database
    {
        return $this->db;
    }
}
