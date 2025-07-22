<?php

namespace App\Scrappers;

use DiDom\Document;

interface ScrapperInterface
{
    public function run(string $url): array;
}
