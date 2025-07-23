<?php

namespace App\Scrappers;

use Exception;

class StartPageScrapper extends AbstractScrapper
{
    public function run(string $url): void
    {
        try {
            $link = [
                'url' => $url,
                'class' => LettersPageScrapper::class
            ];

            $this->linkService->addLink($this->links, $link);
            $this->logger->info("Start page queued");
        } catch (Exception $e) {
            $this->logger->error("Error with parsing: $url", [
                'message' => $e->getMessage()
            ]);
        }
    }
}