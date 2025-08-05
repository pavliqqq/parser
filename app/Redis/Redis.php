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

    public function lPopRPush(string $fromQueue, string $toQueue): ?array
    {
        $command = "
            local link = redis.call('LPOP', KEYS[1])
                if link then redis.call('RPUSH', KEYS[2], link)
                end
            return link
            ";
        $result = $this->client->eval($command, 2, $fromQueue, $toQueue);
        return $result ? json_decode($result, true) : null;
    }

    public function lRemLPush(string $fromQueue, string $toQueue, array $data): bool
    {
        $command = "
            local processedQueue = KEYS[1]
            local linksQueue = KEYS[2]
            local link = ARGV[1]
            local removed = redis.call('LREM', processedQueue, 0, link)
                if removed > 0 then
                    redis.call('LPUSH', linksQueue, link)
                end
            return removed
            ";
        $result = $this->client->eval($command, 2, $fromQueue, $toQueue, json_encode($data));
        return $result > 0;
    }

    public function lRemRPush(string $fromQueue, string $toQueue, array $data): bool
    {
        $command = "
            local processedQueue = KEYS[1]
            local linksQueue = KEYS[2]
            local link = ARGV[1]
            local removed = redis.call('LREM', processedQueue, 0, link)
                if removed > 0 then
                    redis.call('RPUSH', linksQueue, link)
                end
            return removed
            ";
        $result = $this->client->eval($command, 2, $fromQueue, $toQueue, json_encode($data));
        return $result > 0;
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
