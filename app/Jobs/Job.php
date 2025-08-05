<?php

namespace App\Jobs;

use App\Services\DataService;
use App\Services\DocumentService;
use App\Services\LinkService;
use Exception;
use Monolog\Logger;

class Job
{
    protected Logger $logger;

    protected LinkService $linkService;
    protected DataService $dataService;
    protected DocumentService $documentService;
    protected ?array $currentLink = null;

    protected string $links;
    protected string $errorLinks;

    public function __construct(
        Logger          $logger,
        LinkService     $linkService,
        DataService     $dataService,
        DocumentService $documentService
    ) {
        $config = require __DIR__ . '/../../config/redis.php';
        $this->links = $config['queue']['links'];
        $this->errorLinks = $config['queue']['error'];

        $this->logger = $logger;
        $this->linkService = $linkService;
        $this->dataService = $dataService;
        $this->documentService = $documentService;
    }

    public function handle(): void
    {
        pcntl_signal(SIGTERM, function () {
            $this->onShutdown();
            exit(0);
        });

        $limitSeconds = 5;
        $countSeconds = 0;

        while (true) {
            pcntl_signal_dispatch();

            $link = $this->linkService->getLink($this->links);
            $this->currentLink = $link;
            if (!$link) {
                $link = $this->linkService->getLink($this->errorLinks);
                $this->currentLink = $link;
                if (!$link) {
                    sleep(1);
                    $this->logger->info("Waiting links...");
                    $countSeconds++;

                    if ($countSeconds >= $limitSeconds) {
                        $this->logger->info(
                            "Reached limit {$limitSeconds}s. Stopping child " . getmypid() . "..."
                        );
                        exit(0);
                    }
                    continue;
                }
            }
            $countSeconds = 0;

            try {
                $model = new $link['class'](
                    $this->documentService,
                    $this->linkService,
                    $this->dataService,
                    $this->logger
                );
                $model->run($link['url']);
                $this->currentLink = null;
            } catch (Exception $e) {
                $this->logger->error('Error with processing url: ' . $link['url'], [
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function onShutdown(): void
    {
        if ($this->currentLink) {
            $this->linkService->returnLink($this->currentLink);
        } else {
            $this->logger->info("Child has nothing to return");
        }
    }
}
