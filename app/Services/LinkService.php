<?php

namespace App\Services;

use App\Redis\Redis;
use GuzzleHttp\Client;
use Monolog\Logger;
use Exception;

class LinkService
{
    protected Redis $redis;
    protected Logger $logger;
    protected Client $client;
    protected string $baseUrl;

    protected string $queueTableName;

    public function __construct(Redis $redis, Logger $logger, Client $client, string $baseUrl)
    {
        $this->redis = $redis;
        $this->logger = $logger;
        $this->client = $client;
        $this->baseUrl = $baseUrl;
    }

    public function addLink(string $key, array $urls): void
    {
        foreach ($urls as $url) {
            $link = $this->baseUrl . '/' . $url['url'];
            try {
                $client = $this->redis->getClient();
                $result = false;
                if (!$client->sismember('urls', $link)) {
                    $client->sadd('urls', [$link]);
                    $client->rpush($key, [json_encode([
                        'url' => $link,
                        'class' => $url['class'],
                    ])]);
                    $result = true;
                }
                if ($result) {
                    $this->logger->info("Insert link: $link");
                } else {
                    $this->logger->error("This link is already in list");
                }
            } catch (Exception $e) {
                $this->logger->error("Insert link error", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()]);
            }
        }
    }

    public function getLink()
    {
        try {
            $client = $this->redis->getClient();
            $rawLink = $client->lpop('urlsQueue');

            if ($rawLink) {
                $link = json_decode($rawLink, true);

                $client->rpush('processedUrls', [json_encode($link)]);
                $this->logger->info('Get link: ' . $link['url']);
                return $link;
            } else {
                $this->logger->error("This link is missing in queue");
                return null;
            }
        } catch (Exception $e) {
            $this->logger->error('Get link error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()]);
        }
    }
}
