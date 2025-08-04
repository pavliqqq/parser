<?php

namespace App\Services;

use App\Database\Database;
use Monolog\Logger;
use PDOException;

class DataService
{
    protected Database $dataBase;
    protected Logger $logger;
    protected string $questionsTableName;
    protected string $answersTableName;
    protected string $questionAnswerTableName;

    public function __construct(Database $dataBase, Logger $logger)
    {
        $config = require __DIR__ . '/../../config/db.php';

        $this->questionsTableName = $config['data_table']['questions'];
        $this->answersTableName = $config['data_table']['answers'];
        $this->questionAnswerTableName = $config['data_table']['question_answer'];

        $this->dataBase = $dataBase;
        $this->logger = $logger;
    }

    public function insertQuestionsAndAnswers(array $rows): void
    {
        $this->dataBase->beginTransaction();
        try {
            $columns = array_keys($rows[0]);

            $questionColumn = $columns[0];
            $answerColumn = $columns[1];

            foreach ($rows as $row) {
                $questionData = $row[$questionColumn];
                $answerData = $row[$answerColumn];

                $questionId = $this->getId($this->questionsTableName, $columns[0], $questionData);
                $answerId = $this->getId($this->answersTableName, $columns[1], $answerData);

                $data = [
                    'question_id' => $questionId,
                    'answer_id' => $answerId,
                ];

                $this->linkQuestionToAnswer($data);
            }
            $this->dataBase->commit();
        } catch (PDOException $e) {
            $this->dataBase->rollback();
            $this->logger->error("Insert data error", [
                'message' => $e->getMessage()
            ]);
        }
    }

    private function getId(string $table, string $column, string $data)
    {
        $sql = sprintf(
            "SELECT id FROM %s WHERE %s = ?",
            $table,
            $column
        );

        $stmt = $this->dataBase->query($sql, [$data]);

        $id = $stmt->fetchColumn();

        if (!$id) {
            return $this->insertData($table, $column, $data);
        }

        return $id;
    }

    private function insertData(string $table, string $column, string $data): string
    {
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (?)",
            $table,
            $column
        );

        $this->dataBase->query($sql, [$data]);
        return $this->dataBase->lastInsertId();
    }

    private function linkQuestionToAnswer(array $data): void
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => "?", $columns);

        $sql = sprintf(
            "INSERT IGNORE INTO %s (%s) VALUES (%s)",
            $this->questionAnswerTableName,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->dataBase->query($sql, array_values($data));
    }
}
