<?php

namespace App\Services;

use App\Database\Database;
use Monolog\Logger;
use PDOException;

class DataService
{
    protected Database $dataBase;
    protected Logger $logger;
    protected string $dataTableName;

    public function __construct(Database $dataBase, Logger $logger)
    {
        $config = require __DIR__ . '/../../config/db.php';

        $this->dataTableName = $config['data_table']['name'];

        $this->dataBase = $dataBase;
        $this->logger = $logger;
    }

    public function insertData(array $rows): void
    {
        try {
            $columns = array_keys($rows[0]);
            $columnCount = count($columns);

            $placeholders = [];
            $values = [];

            foreach ($rows as $row) {
                $placeholders[] = '(' . rtrim(str_repeat('?,', $columnCount), ',') .')';

                foreach ($columns as $column) {
                    $values[] = $row[$column];
                }
            }

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES %s",
                $this->dataTableName,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $stmt = $this->dataBase->query($sql, $values);
            $rowsCount = $stmt->rowCount();

            $this->logger->info("Insert rows: $rowsCount");
        } catch (PDOException $e) {
            $this->logger->error("Insert data error", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
