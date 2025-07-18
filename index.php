<?php

use App\Database\Database;
use App\Services\DataService;
use App\Services\QueryLinkService;
use App\Jobs\Job;
use Dotenv\Dotenv;
use GuzzleHttp\Client;

set_time_limit(0);
ini_set('memory_limit', -1);

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

require __DIR__ . '/funcs.php';
$logger = require __DIR__ . '/logger.php';

$client = new Client();
$db = new Database();

$baseUrl = 'https://www.kreuzwort-raetsel.net';

echo "Start parsing... \n";
$url = 'uebersicht.html';

$linkService = new QueryLinkService($db, $logger, $client, $baseUrl);
$dataService = new DataService($db, $logger);

$linkService->addLink($url);

$threads = 2;

for ($i = 0; $i < $threads; $i++) {
    $pid = pcntl_fork();

    if ($pid == -1) {
        die("Could not fork");
    } elseif ($pid) {
        echo "Parent start \n";
    } else {
        echo "Child #$i (PID: " . getmypid() . ") start\n";

        $worker = new Job($logger, $client, $linkService, $dataService);
        $worker->handle();
        exit(0);
    }
}

while (($pid = pcntl_wait($status)) !== -1) {
    echo "Child $pid ended\n";
}
