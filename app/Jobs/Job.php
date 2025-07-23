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

    public function __construct(
        Logger $logger,
        LinkService $linkService,
        DataService $dataService,
        DocumentService $documentService
    ) {
        $this->logger = $logger;
        $this->linkService = $linkService;
        $this->dataService = $dataService;
        $this->documentService = $documentService;
    }

    public function handle():void
    {
        pcntl_signal(SIGTERM, function () {
            $this->onShutdown();
            exit(0);
        });

        $limitSeconds = 5;
        $countSeconds = 0;

        while (true) {
            $link = $this->linkService->getLink();
            if (!$link) {
                sleep(1);
                $countSeconds++;

                if ($countSeconds >= $limitSeconds) {
                    $this->logger->info("Reached limit {$limitSeconds}s. Parser stopping...");

                    $parentPid = posix_getppid();
                    posix_kill($parentPid, SIGINT);

                    exit(0);
                }

                continue;
            }
            $countSeconds = 0;

            $this->currentLink = $link;
            try {
                $model = new $link['class'](
                    $this->documentService,
                    $this->linkService,
                    $this->dataService,
                    $this->logger
                );
                $model->run($link['url']);
            } catch (Exception $e) {
                $this->logger->error('Error with processing url:' . $link['url'], [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            } finally {
                $this->currentLink = null;
            }
        }
    }

    private function onShutdown():void
    {
        if ($this->currentLink) {
            $this->linkService->returnLink($this->currentLink);
        } else {
            $this->logger->info("Nothing to return");
        }
    }
}
