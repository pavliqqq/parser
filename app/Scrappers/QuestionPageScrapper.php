<?php

namespace App\Scrappers;

use DiDom\Document;
use Exception;

class QuestionPageScrapper extends AbstractScrapper
{
    public function run(string $url): void
    {
        try {
            $document = $this->documentService->createDocument($url);
            if ($document === null) {
                $this->moveToErrorQueue($url);
                return;
            }

            $letter = $this->getLetter($document);

            $rows = $document->find("#Searchresults table tbody tr");

            $result = [];

            foreach ($rows as $row) {
                $questionTd = $row->first('td.Question a');
                $answerTd = $row->first('td.AnswerShort a');

                $question = $questionTd->text();
                $answer = $answerTd->text();


                $result[] = [
                    'question' => $question,
                    'answer' => $answer,
                ];
            }
            $this->dataService->insertQuestionsAndAnswers($result);

            $count = count($result);
            $this->logger->info("Parsed $count rows of letter $letter");
        } catch (Exception $e) {
            $this->logger->error("Error with parsing: $url", [
                'message' => $e->getMessage()
            ]);
        }
    }

    private function getLetter(Document $document): string
    {
        $headerString = $document->find("div.Text h2")[0]->text();
        $parts = explode(' ', trim($headerString));
        return end($parts);
    }
}
