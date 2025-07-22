<?php

namespace App\Scrappers;

use App\Services\DataService;
use App\Services\DocumentService;
use App\Services\LinkService;
use Monolog\Logger;

abstract class AbstractScrapper implements ScrapperInterface
{
    protected DocumentService $documentService;
    protected LinkService $linkService;
    protected DataService $dataService;
    protected Logger $logger;
    public function __construct(DocumentService $documentService, LinkService $linkService, DataService $dataService, Logger $logger)
    {
        $this->documentService = $documentService;
        $this->linkService = $linkService;
        $this->dataService = $dataService;
        $this->logger = $logger;
    }

    abstract public function run(string $url): array;
}
