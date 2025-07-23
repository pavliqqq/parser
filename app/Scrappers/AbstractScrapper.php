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
    protected string $links;
    protected string $processedLinks;
    protected string $errorLinks;
    public function __construct(
        DocumentService $documentService,
        LinkService $linkService,
        DataService $dataService,
        Logger $logger
    ) {
        $config = require __DIR__ . '/../../config/redis.php';
        $this->links = $config['queue']['links'];
        $this->processedLinks = $config['queue']['processed'];
        $this->errorLinks = $config['queue']['error'];

        $this->documentService = $documentService;
        $this->linkService = $linkService;
        $this->dataService = $dataService;
        $this->logger = $logger;
    }

    abstract public function run(string $url): void;

    public function moveToErrorQueue(string $url): void
    {
        $this->linkService->moveLinkToErrorQueue([
            'url' => $url,
            'class' => static::class
        ]);
    }
}
