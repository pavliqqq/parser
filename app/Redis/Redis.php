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
                'scheme' => $config['scheme'],
                'host' => $config['host'],
                'port' => $config['port'],
                'password' => $config['password'],
                'database' => $config['database'],
            ]);
        } catch (Exception $e) {
            die("Redis connection failed: " . $e->getMessage());
        }
    }

    public function getClient()
    {
        return $this->client;
    }

    public function set(string $queue, array $data)
    {
        $this->client->rpush($queue, $data);
    }

    public function get(string $queue)
    {
        return $this->client->lpop($queue);
    }
}
