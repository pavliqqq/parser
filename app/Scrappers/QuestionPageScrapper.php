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
            $this->dataService->insertQuestionsAndAnswers($result, $url);
        } catch (Exception $e) {
            $this->logger->error("Error with parsing", [
                'pid' => getmypid(),
                'url' => $url,
                'message' => $e->getMessage()
            ]);
        }
    }
}
