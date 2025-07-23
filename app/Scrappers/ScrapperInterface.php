<?php

namespace App\Scrappers;

interface ScrapperInterface
{
    public function run(string $url): void;
}
