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

    public function sAddRPush(string $setKey, string $queueKey, array $data, string $setData): void
    {
        $command = "
            redis.call('SADD', KEYS[1], ARGV[1])
            redis.call('RPUSH', KEYS[2], ARGV[2])
            ";
        $this->client->eval($command, 2, $setKey, $queueKey, $setData, json_encode($data));
    }

    public function lPopRPush(string $fromQueue, string $toQueue): ?array
    {
        $command = "
            local link = redis.call('LPOP', KEYS[1])
                if link then redis.call('RPUSH', KEYS[2], link) end
            return link
            ";
        return json_decode($this->client->eval($command, 2, $fromQueue, $toQueue), true);
    }

    public function lRemLPush(string $fromQueue, string $toQueue, array $data): bool
    {
        $command = "
            local removed = redis.call('LREM', KEYS[1], 0, ARGV[1])
                if removed > 0 then
                    redis.call('LPUSH', KEYS[2], ARGV[1])
                end
            return removed
            ";
        $result = $this->client->eval($command, 2, $fromQueue, $toQueue, json_encode($data));
        return $result > 0;
    }

    public function lRemRPush(string $fromQueue, string $toQueue, array $data): bool
    {
        $command = "
            local removed = redis.call('LREM', KEYS[1], 0, ARGV[1])
                if removed > 0 then
                    redis.call('RPUSH', KEYS[2], ARGV[1])
                end
            return removed
            ";
        $result = $this->client->eval($command, 2, $fromQueue, $toQueue, json_encode($data));
        return $result > 0;
    }

    public function sIsMember(string $key, string $data): bool
    {
        return $this->client->sismember($key, $data);
    }
}
