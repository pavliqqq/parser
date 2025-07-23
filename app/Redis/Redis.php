<?php

namespace App\Redis;

use Exception;
use Predis\Client;

class Redis
{
    private Client $client;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/redis.php';

        try {
            $this->client = new Client([
                'scheme' => $config['connection']['scheme'],
                'host' => $config['connection']['host'],
                'port' => $config['connection']['port'],
                'password' => $config['connection']['password'],
                'database' => $config['connection']['database'],
            ]);
        } catch (Exception $e) {
            die("Redis connection failed: " . $e->getMessage());
        }
    }

    public function rPush(string $queue, array $data): void
    {
        $this->client->rpush($queue, [json_encode($data)]);
    }

    public function lPush(string $queue, array $data): void
    {
        $this->client->lpush($queue, [json_encode($data)]);
    }

    public function pop(string $queue): ?array
    {
        $raw = $this->client->lpop($queue);
        return $raw ? json_decode($raw, true) : null;
    }

    public function removeElement(string $queue, array $data): bool
    {
        return $this->client->lrem($queue, 0, json_encode($data)) > 0;
    }

    public function sAdd(string $key, array $data): void
    {
        $this->client->sadd($key, $data);
    }

    public function sIsMember(string $key, string $data): bool
    {
        return $this->client->sismember($key, $data);
    }
}
