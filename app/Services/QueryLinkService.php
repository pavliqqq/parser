<?php

namespace App\Services;

use App\Database\Database;
use GuzzleHttp\Client;
use Monolog\Logger;
use PDOException;

class QueryLinkService
{
    protected Database $dataBase;
    protected Logger $logger;
    protected Client $client;
    protected string $baseUrl;

    protected string $queueTableName;

    public function __construct(Database $dataBase, Logger $logger, Client $client, string $baseUrl)
    {
        $this->dataBase = $dataBase;
        $this->logger = $logger;
        $this->client = $client;
        $this->baseUrl = $baseUrl;
    }

    public function addLink(string $url)
    {
        $url = $this->baseUrl . '/' . $url;
        try {
            $sql = "SELECT * FROM queue WHERE url = ?";
            $stmt = $this->dataBase->query($sql, [$url]);
            if (empty($stmt->fetch())) {
                $sql = "INSERT IGNORE INTO queue (url, status) VALUES (?,?)";
                $this->dataBase->query($sql, [$url, 'pending']);
                $this->logger->info("Insert link: $url");
            } else {
                $this->logger->info("Link is already in queue: $url");
            }
        } catch (PDOException $e) {
            $this->logger->error("Insert link error", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
        }
    }

    public function getLink(string $columnName, string $status)
    {
        try {
            $sql = "SELECT $columnName FROM queue WHERE status = ? LIMIT 1";
            $stmt = $this->dataBase->query($sql, [$status]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Job get link error", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
        }
    }

    public function changeStatus(string $status, string $url): void
    {
        try {
            $sql = "UPDATE queue SET status = ? WHERE url = ?";
            $this->dataBase->query($sql, [$status, $url]);
        } catch (PDOException $e) {
            $this->logger->error("Job change status error", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
        }
    }
}
