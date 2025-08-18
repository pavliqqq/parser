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

            $this->logger->info("Queueing start page...");
            $this->linkService->addLink($this->links, $link);
        } catch (Exception $e) {
            $this->logger->error("Error with parsing", [
                'pid' => getmypid(),
                'url' => $url,
                'message' => $e->getMessage()
            ]);
        }
    }
}