<?php

namespace App\Services;

use DiDom\Document;
use Exception;
use GuzzleHttp\Client;
use Monolog\Logger;

class DocumentService
{
    protected Client $client;
    protected Logger $logger;
    protected array $proxies = [];

    public function __construct(Client $client, Logger $logger)
    {
        $config = require __DIR__ . '/../../config/proxy.php';

        $this->client = $client;
        $this->logger = $logger;

        $this->loadProxiesFromFile($config['proxy_file']);
    }

    private function loadProxiesFromFile(string $filename): void
    {
        if (!file_exists($filename)) {
            $this->logger->warning("Proxy file not found: $filename");
            return;
        }
        $lines = file($filename);
        $this->proxies = array_map('trim', $lines);
    }

    private function getRandomProxy(): ?array
    {
        if (empty($this->proxies)) {
            return null;
        }

        $line = $this->proxies[array_rand($this->proxies)];

        $parts = explode(':', $line);
        if (count($parts) !== 4) {
            $this->logger->warning("Invalid proxy format: $line");
            return null;
        }

        [$ip, $port, $user, $pass] = $parts;

        return [
            'ip' => $ip,
            'port' => $port,
            'user' => $user,
            'pass' => $pass,
        ];
    }

    private function getHtml(string $url): ?string
    {
        $maxAttempts = 5;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $proxy = $this->getRandomProxy();

            if (!$proxy) {
                $this->logger->warning("No proxy available for $url");
                break;
            }

            $proxyString = "http://{$proxy['user']}:{$proxy['pass']}@{$proxy['ip']}:{$proxy['port']}";

            try {
                $response = $this->client->get($url, [
                    'verify' => false,
                    'proxy' => $proxyString,
                    'timeout' => 10,
                    'connect_timeout' => 5
                ]);

                $status = $response->getStatusCode();

                if ($status === 200) {
                    $this->logger->info("Load $url, status: " . $status);
                    return $response->getBody()->getContents();
                } else {
                    return '';
                }
            } catch (Exception $e) {
                $this->logger->error("Attempt $attempt failed for $url", [
                    'proxy' => $proxyString,
                    'message' => $e->getMessage()
                ]);
            }
        }
        return '';
    }

    public function createDocument(string $url): ?Document
    {
        $html = $this->getHtml($url);

        if (!$html) {
            return null;
        }

        $document = new Document();
        $document->loadHtml($html);

        return $document;
    }
}
