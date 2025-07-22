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

        $host = $config['host'];
        $dbName = $config['dbname'];
        $user = $config['user'];
        $password = $config['password'];

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
}
