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

    public function __construct(Logger $logger, LinkService $linkService, DataService $dataService, DocumentService $documentService)
    {
        $this->logger = $logger;
        $this->linkService = $linkService;
        $this->dataService = $dataService;
        $this->documentService = $documentService;
    }

    public function handle()
    {
        pcntl_signal_dispatch();
        while (true) {
            $link = $this->linkService->getLink();
            if (!$link) {
                sleep(1);
                continue;
            }

            try {
                $model = new $link['class']($this->documentService, $this->linkService, $this->dataService, $this->logger);
                $model->run($link['url']);
            } catch (Exception $e) {
                $this->logger->error('Error with processing url:' . $link['url'], [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}
