<?php

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private function connect(): PDO
    {
        $config = require __DIR__ . '/../../config/db.php';
        $dbConfig = $config['db'];

        $host = $dbConfig['host'];
        $dbName = $dbConfig['dbname'];
        $user = $dbConfig['user'];
        $password = $dbConfig['password'];

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $user, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function query(string $sql, array $params = [])
    {
        $conn = $this->connect();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
