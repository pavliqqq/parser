<?php

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private PDO $conn;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/db.php';

        $host = $config['connection']['host'];
        $dbName = $config['connection']['dbname'];
        $user = $config['connection']['user'];
        $password = $config['connection']['password'];

        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $user, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function query(string $sql, array $params = [])
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'MySQL server has gone away')) {
                $this->reconnect();
                return $this->query($sql, $params);
            }
            throw $e;
        }
    }

    private function reconnect(): void
    {
        $this->__construct();
    }

    public function lastInsertId(): string
    {
        return $this->conn->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->conn->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->conn->commit();
    }

    public function rollback(): bool
    {
        return $this->conn->rollBack();
    }
}
