<?php

namespace BrewMo\Tests\Mocks;

use stdClass;

/**
 * Mock implementation of Dolibarr's DoliDB for testing purposes
 * This allows testing repository classes without a real database connection
 */
class MockDoliDB
{
    private array $tables = [];
    private array $lastInsertIds = [];
    private array $queries = [];
    private int $affectedRows = 0;

    public function __construct()
    {
        // Initialize with empty tables
        $this->tables = [
            'llx_brew_session_v2' => [],
            'llx_brew_recipes' => [],
            'llx_brew_container' => [],
            'llx_brew_vessels' => [],
            'llx_brew_tank' => [],
        ];
    }

    /**
     * Simulate a SQL query
     */
    public function query(string $sql, array $params = []): bool|object
    {
        $this->queries[] = ['sql' => $sql, 'params' => $params];

        // Parse the SQL to determine the action
        $sql = strtoupper(trim($sql));

        if (str_starts_with($sql, 'SELECT')) {
            return $this->handleSelect($sql, $params);
        } elseif (str_starts_with($sql, 'INSERT')) {
            return $this->handleInsert($sql, $params);
        } elseif (str_starts_with($sql, 'UPDATE')) {
            return $this->handleUpdate($sql, $params);
        } elseif (str_starts_with($sql, 'DELETE')) {
            return $this->handleDelete($sql, $params);
        }

        return true;
    }

    /**
     * Get the last insert ID for a table
     */
    public function last_insert_id(string $table): int
    {
        if (isset($this->lastInsertIds[$table])) {
            return $this->lastInsertIds[$table];
        }
        return 1;
    }

    /**
     * Get the number of rows affected by the last query
     */
    public function affected_rows(): int
    {
        return $this->affectedRows;
    }

    /**
     * Get the number of rows in a result
     */
    public function num_rows($result): int
    {
        if (is_object($result) && isset($result->rows)) {
            return count($result->rows);
        }
        return 0;
    }

    /**
     * Fetch an object from a result
     */
    public function fetch_object($result): ?stdClass
    {
        if (is_object($result) && isset($result->rows) && count($result->rows) > 0) {
            $row = array_shift($result->rows);
            $obj = new stdClass();
            foreach ($row as $key => $value) {
                $obj->$key = $value;
            }
            return $obj;
        }
        return null;
    }

    /**
     * Get the last error
     */
    public function lasterror(): string
    {
        return '';
    }

    /**
     * Escape a string (mock implementation)
     */
    public function escape(string $str): string
    {
        return addslashes($str);
    }

    /**
     * Get table prefix
     */
    public function prefix(): string
    {
        return 'llx_';
    }

    /**
     * Begin transaction
     */
    public function begin(): bool
    {
        return true;
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return true;
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return true;
    }

    /**
     * Format date for database
     */
    public function idate(string $date): string
    {
        return date('Y-m-d', strtotime($date));
    }

    /**
     * Format date from database
     */
    public function jdate(string $date): string
    {
        return $date;
    }

    /**
     * Handle SELECT queries
     */
    private function handleSelect(string $sql, array $params): object
    {
        $result = new stdClass();
        $result->rows = [];

        // Extract table name and conditions
        if (preg_match('/FROM\s+(\w+)/i', $sql, $matches)) {
            $tableName = $matches[1];
            
            if (isset($this->tables[$tableName])) {
                $result->rows = $this->tables[$tableName];
            }
        }

        // Apply WHERE conditions if params are provided
        if (!empty($params) && !empty($result->rows)) {
            $result->rows = array_filter($result->rows, function($row) use ($params) {
                // Simple filtering - in a real mock, this would be more sophisticated
                foreach ($params as $param) {
                    foreach ($row as $value) {
                        if (is_string($value) && str_contains($value, $param)) {
                            return true;
                        }
                        if (is_int($value) && $value == $param) {
                            return true;
                        }
                    }
                }
                return false;
            });
        }

        return $result;
    }

    /**
     * Handle INSERT queries
     */
    private function handleInsert(string $sql, array $params): bool
    {
        // Extract table name
        if (preg_match('/INTO\s+(\w+)/i', $sql, $matches)) {
            $tableName = $matches[1];
            
            if (!isset($this->tables[$tableName])) {
                $this->tables[$tableName] = [];
            }

            // Create a new row with the params
            $row = [];
            // In a real implementation, we'd map params to column names
            // For now, just create a simple row
            $newId = count($this->tables[$tableName]) + 1;
            $row['rowid'] = $newId;
            
            // Store the last insert ID
            $this->lastInsertIds[$tableName] = $newId;
            
            // Add the row to the table
            $this->tables[$tableName][] = $row;
            $this->affectedRows = 1;
        }

        return true;
    }

    /**
     * Handle UPDATE queries
     */
    private function handleUpdate(string $sql, array $params): bool
    {
        $this->affectedRows = 0;
        
        // Extract table name
        if (preg_match('/UPDATE\s+(\w+)/i', $sql, $matches)) {
            $tableName = $matches[1];
            
            if (isset($this->tables[$tableName])) {
                foreach ($this->tables[$tableName] as &$row) {
                    // In a real implementation, we'd check WHERE conditions
                    // For now, just update all rows
                    $row['updated'] = true;
                    $this->affectedRows++;
                }
            }
        }

        return true;
    }

    /**
     * Handle DELETE queries
     */
    private function handleDelete(string $sql, array $params): bool
    {
        $this->affectedRows = 0;
        
        // Extract table name
        if (preg_match('/DELETE\s+FROM\s+(\w+)/i', $sql, $matches)) {
            $tableName = $matches[1];
            
            if (isset($this->tables[$tableName])) {
                // In a real implementation, we'd check WHERE conditions
                // For now, just clear the table
                $this->tables[$tableName] = [];
                $this->affectedRows = 1;
            }
        }

        return true;
    }

    /**
     * Add test data to a table
     */
    public function addTestData(string $tableName, array $data): void
    {
        if (!isset($this->tables[$tableName])) {
            $this->tables[$tableName] = [];
        }
        $this->tables[$tableName][] = $data;
    }

    /**
     * Get all queries executed
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Clear all data
     */
    public function clearAll(): void
    {
        $this->tables = [];
        $this->lastInsertIds = [];
        $this->queries = [];
        $this->affectedRows = 0;
    }
}
