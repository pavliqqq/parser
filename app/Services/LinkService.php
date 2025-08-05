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
    protected string $links;
    protected string $processedLinks;
    protected string $errorLinks;

    public function __construct(Redis $redis, Logger $logger, Client $client, string $baseUrl)
    {
        $config = require __DIR__ . '/../../config/redis.php';
        $this->links = $config['queue']['links'];
        $this->processedLinks = $config['queue']['processed'];
        $this->errorLinks = $config['queue']['error'];

        $this->redis = $redis;
        $this->logger = $logger;
        $this->client = $client;
        $this->baseUrl = $baseUrl;
    }

    public function addLink(string $queue, array $rowLink): void
    {
        $rowLink['url'] = $this->baseUrl . '/' . $rowLink['url'];
        $link = $rowLink['url'];

        try {
            if (!$this->redis->sIsMember('urls', $link)) {
                $this->redis->sAdd('urls', [$link]);
                $this->redis->rPush($queue, $rowLink);

                $this->logger->info("Insert link: $link");
            } else {
                $this->logger->info("This link is already in list");
            }
        } catch (Exception $e) {
            $this->logger->error("Error while inserting link to queue '$queue'", [
                'url' => $link,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getLink(string $queue): ?array
    {
        try {
            $rowLink = $this->redis->lPopRPush($queue, $this->processedLinks);
            if ($rowLink) {
                $this->logger->info('Get link: ' . $rowLink['url']);
                return $rowLink;
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->logger->error('Error while getting link', [
                'message' => $e->getMessage()
            ]);
        }
        return null;
    }

    public function returnLink(array $link): void
    {
        try {
            $removed = $this->redis->lRemLPush($this->processedLinks, $this->links, $link);

            if ($removed) {
                $this->logger->info("Returned link back to '{$this->links}': {$link['url']}");
            } else {
                $this->logger->warning("Link not found in '{$this->processedLinks}': {$link['url']}");
            }
        } catch (Exception $e) {
            $this->logger->error('Error while returning link', [
                'message' => $e->getMessage()
            ]);
        }
    }

    public function moveLinkToErrorQueue(array $link): void
    {
        try {
            $removed = $this->redis->lRemRPush($this->processedLinks, $this->errorLinks, $link);

            if ($removed) {
                $this->logger->info("Link moved to '{$this->errorLinks}': {$link['url']}");
            } else {
                $this->logger->warning("Link not found in '{$this->processedLinks}': {$link['url']}");
            }
        } catch (Exception $e) {
            $this->logger->error('Error while moving link', [
                'message' => $e->getMessage()
            ]);
        }
    }
}
