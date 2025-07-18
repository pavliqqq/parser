<?php

use DiDom\Document;
use GuzzleHttp\Client;
use Monolog\Logger;

function getHtml(string $url, Client $client, Logger $logger): string
{
    try {
        $response = $client->get($url, ['verify' => false]);
        $logger->info("Load $url");

        return $response->getBody()->getContents();
    } catch (Exception $e) {
        $logger->error("Error with url: $url ", [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return '';
    }
}

function createDocument(string $url, Client $client, Logger $logger): ?Document
{
    $mainHtml = getHtml($url, $client, $logger);

    if (!empty($mainHtml)) {
        $document = new Document();
        $document->loadHtml($mainHtml);

        return $document;
    } else {
        $logger->warning("Html is empty.");
        return null;
    }
}

function checkUrl(string $url, Client $client, Logger $logger): array
{
    $document = createDocument($url, $client, $logger);

    if (!$document) {
        $logger->warning("Skip $url because HTML is empty.");
    }

    if ($document->has("ul.dnrg li a")) {
        $links = parsingLinks($document, $logger);
        return ['type' => 'links', 'data' => $links];
    } elseif ($document->has("#Searchresults table tbody tr")) {
        $data = parsingData($document, $logger);
        return ['type' => 'data', 'data' => $data];
    }

    return ['type' => 'empty', 'data' => []];
}

function parsingLinks(Document $document, Logger $logger): array
{
    try {
        $urls = [];
        $links = $document->find("ul.dnrg li a");
        foreach ($links as $link) {
            $href = $link->attr('href');
            $urls[] = $href;
        }
        $count = count($urls);
        $logger->info("Parsed $count links");

        return $urls;
    } catch (Exception $e) {
        $logger->error("Error with parsing: ", [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return [];
    }
}

function getLetter(Document $document): string
{
    $headerString = $document->find("div.Text h2")[0]->text();
    $parts = explode(' ', trim($headerString));
    return end($parts);
}

function parsingData(Document $document, Logger $logger): array
{
    try {
        $letter = getLetter($document);

        $rows = $document->find("#Searchresults table tbody tr");

        $result = [];

        foreach ($rows as $row) {
            $questionTd = $row->first('td.Question a');
            $answerTd = $row->first('td.AnswerShort a');

            $question = $questionTd->text();
            $answer = $answerTd->text();


            $result[] = [
                'letter' => $letter,
                'question' => $question,
                'answer' => $answer,
            ];
        }
        $count = count($result);
        $logger->info("Parsed $count rows of letter $letter");

        return $result;
    } catch (Exception $e) {
        $logger->error("Error with parsing: ", [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return [];
    }
}
