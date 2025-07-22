<?php

namespace App\Scrappers;

use Exception;

class StartPageScrapper extends AbstractScrapper
{
    public function run(string $url): array
    {
        try {
            $urls = [[
                'url' => $url,
                'class' => LettersPageScrapper::class
            ]];

            $this->linkService->addLink('urlsQueue', $urls);
            $this->logger->info("Start page queued");

            return $urls;
        } catch (Exception $e) {
            $this->logger->error("Error with parsing: $url", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}