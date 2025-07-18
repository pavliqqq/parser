<?php

namespace App\Jobs;

use App\Services\DataService;
use App\Services\QueryLinkService;
use Exception;
use GuzzleHttp\Client;
use Monolog\Logger;

class Job
{
    protected Logger $logger;
    protected Client $client;

    protected QueryLinkService $linkService;
    protected DataService $dataService;

    public function __construct(Logger $logger, Client $client, QueryLinkService $linkService, DataService $dataService)
    {
        $this->logger = $logger;
        $this->client = $client;
        $this->linkService = $linkService;
        $this->dataService = $dataService;
    }

    public function handle()
    {
        while (true) {
            $row = $this->linkService->getLink('url', 'pending');

            if ($row === false) {
                sleep(1);
                continue;
            }

            $url = $row['url'];

            $this->linkService->changeStatus('processing', $url);

            try {
                $data = checkUrl($url, $this->client, $this->logger);

                if ($data['type'] === 'links') {
                    foreach ($data['data'] as $link) {
                        $this->linkService->addLink($link);
                    }
                } elseif ($data['type'] === 'data') {
                    $this->dataService->insertData($data['data']);
                } else {
                    $this->logger->error("Cant check url: $url");
                }

                $this->linkService->changeStatus('done', $url);
            } catch (Exception $e) {
                $this->logger->error("Error with processing url: $url", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->linkService->changeStatus('failed', $url);
            }
        }
    }
}
