<?php

namespace App\Scrappers;

use Exception;

class LettersPageScrapper extends AbstractScrapper
{
    public function run(string $url): array
    {
        try {
            $document = $this->documentService->createDocument($url);

            $urls = [];
            $links = $document->find("ul.dnrg li a");

            foreach ($links as $link) {
                $href = $link->attr('href');
                $urls[] = [
                    'url' => $href,
                    'class' => PaginationPageScrapper::class
                ];
            }
            $this->linkService->addLink('urlsQueue', $urls);
            $count = count($urls);
            $this->logger->info("Parsed $count links");

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