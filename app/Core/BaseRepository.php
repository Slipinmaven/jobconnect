<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * BaseRepository - All repositories extend this
 * 
 * PURPOSE: Handles all database queries for a specific entity
 * 
 * WHY USE REPOSITORIES:
 * - Centralizes database logic
 * - Makes code testable (can mock repository)
 * - Keeps SQL out of controllers/services
 * - Easy to change database without affecting business logic
 * 
 * HOW IT WORKS:
 * 1. Each entity gets its own repository (UserRepository, JobRepository)
 * 2. Repository contains ONLY database queries
 * 3. Services call repository methods
 * 4. Repository returns arrays or objects
 * 
 * EXAMPLE:
 * class UserRepository extends BaseRepository {
 *     protected string $table = 'users';
 *     
 *     public function findByEmail(string $email) {
 *         return $this->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
 *     }
 * }
 */
abstract class BaseRepository
{
    protected PDO $pdo;
    protected string $table;
    protected string $primaryKey = 'id';
    protected ?PDOStatement $lastStatement = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    // ==================== BASIC CRUD ====================

    /**
     * Find record by ID
     * 
     * @param int $id Primary key value
     * @return array|null Record or null if not found
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Find all records
     */
    public function findAll(string $orderBy = 'id DESC', ?int $limit = null): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        return $this->fetchAll($sql);
    }

    /**
     * Find records with conditions
     */
    public function findWhere(array $conditions, string $orderBy = 'id DESC'): array
    {
        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[":{$field}"] = $value;
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " ORDER BY {$orderBy}";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Create a new record
     * 
     * @param array $data Field => value pairs
     * @return int|null The new record ID or null on failure
     */
    public function create(array $data): ?int
{
    // Let MySQL handle timestamps
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));

    $sql = "INSERT INTO {$this->table} ({$columns}, created_at, updated_at) 
            VALUES ({$placeholders}, NOW(), NOW())";

    $success = $this->execute($sql, $data);

    if ($success) {
        return (int)$this->pdo->lastInsertId();
    }

    return null;
}

    /**
     * Update a record
     * 
     * @param int $id Record ID
     * @param array $data Field => value pairs to update
     * @return bool True if successful
     */
    public function update(int $id, array $data): bool
    {
        // Add updated timestamp
        $data['updated_at'] = date('Y-m-d H:i:s');

        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "{$column} = :{$column}";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->primaryKey} = :id";

        $params = array_merge($data, ['id' => $id]);

        return $this->execute($sql, $params);
    }

    /**
     * Delete a record
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->execute($sql, [':id' => $id]);
    }

    /**
     * Delete records by conditions
     */
    public function deleteWhere(array $conditions): bool
    {
        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[":{$field}"] = $value;
        }

        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $where);

        return $this->execute($sql, $params);
    }

    /**
     * Check if record exists
     */
    public function exists(array $conditions): bool
    {
        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[":{$field}"] = $value;
        }

        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE " . implode(' AND ', $where) . " LIMIT 1";

        $result = $this->fetchOne($sql, $params);

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Count records
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";

        if (!empty($conditions)) {
            $where = [];
            $params = [];

            foreach ($conditions as $field => $value) {
                $where[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }

            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $result = $this->fetchOne($sql, $params ?? []);

        return (int)($result['count'] ?? 0);
    }

    /**
     * Build safe LIMIT/OFFSET clause (int cast to prevent SQL injection)
     */
    protected function formatLimitOffset(int $limit, int $offset): string
    {
        $limit = max(0, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        return " LIMIT {$limit} OFFSET {$offset}";
    }
    
    // ==================== QUERY EXECUTION ====================

    /**
     * Execute a query and return statement
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return bool True on success
     * @throws PDOException on error
     */
    public function execute(string $sql, array $params = []): bool
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $this->lastStatement = $stmt; // Store the statement
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log(static::class . " - Query error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            throw $e;
        }
    }

    /**
     * Execute a query and fetch one row
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); // Close the cursor to free resources
            return $result ?: null;
        } catch (PDOException $e) {
            error_log(static::class . " - Fetch error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Execute a query and fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); // Close the cursor to free resources
            return $result;
        } catch (PDOException $e) {
            error_log(static::class . " - Fetch all error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Execute a query and fetch a single column
     */
    protected function fetchColumn(string $sql, array $params = [], int $column = 0)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn($column);
        } catch (PDOException $e) {
            error_log(static::class . " - Fetch column error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // ==================== HELPER METHODS ====================

    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Get the last insert ID
     */
    protected function lastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }
    public function rowCount(): int
    {
        if ($this->lastStatement) {
            return $this->lastStatement->rowCount();
        }
        return 0;
    }
    public function getTable(): string
    {
        return $this->table;
    }
}
