<?php

namespace App\Scrappers;

use Exception;

class LettersPageScrapper extends AbstractScrapper
{
    public function run(string $url): void
    {
        try {
            $document = $this->documentService->createDocument($url);
            if ($document === null) {
                $this->moveToErrorQueue($url);
                return;
            }

            $links = $document->find("ul.dnrg li a");

            $count = 0;
            foreach ($links as $link) {
                $href = $link->attr('href');
                $rowLink = [
                    'url' => $href,
                    'class' => PaginationPageScrapper::class
                ];
                $this->linkService->addLink($this->links, $rowLink);
                $count++;
            }

            $this->logger->info("Parsed $count links from $url");
        } catch (Exception $e) {
            $this->logger->error("Error with parsing: $url", [
                'message' => $e->getMessage()
            ]);
        }
    }
}
