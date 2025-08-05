<?php

namespace App\Fork;

use App\Database\Database;
use App\Jobs\Job;
use App\Redis\Redis;
use App\Scrappers\StartPageScrapper;
use App\Services\DataService;
use App\Services\DocumentService;
use App\Services\LinkService;
use GuzzleHttp\Client;
use Monolog\Logger;

class ForkManager
{
    private Logger $logger;
    private string $baseUrl;
    private string $startUrl;
    private int $threads;
    private int $parentPid;
    private array $pids = [];
    private bool $isStopped = false;

    public function __construct()
    {
        $this->logger = require __DIR__ . '/../../logger.php';

        $config = require __DIR__ . '/../../config/parser.php';
        $this->threads = $config['threads'];

        $this->baseUrl = $config['base_url'];
        $this->startUrl = $config['start_url'];
    }

    public function run(): void
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, [$this, 'stop']);

        $this->parentPid = getmypid();

        $this->logger->info("Start parsing... ");

        $this->runStartPage();

        for ($i = 0; $i < $this->threads; $i++) {
            $pid = pcntl_fork();

            if ($pid == -1) {
                die("Could not fork");
            } elseif ($pid === 0) {
                $this->runChild($i);
            } else {
                $this->pids[] = $pid;
            }
        }

        $this->waitChildren();
    }

    private function runStartPage(): void
    {
        $client = new Client();
        $redis = new Redis();
        $db = new Database();

        $documentService = new DocumentService($client, $this->logger);
        $linkService = new LinkService($redis, $this->logger, $client, $this->baseUrl);
        $dataService = new DataService($db, $this->logger);

        $startScrapper = new StartPageScrapper($documentService, $linkService, $dataService, $this->logger);
        $startScrapper->run($this->startUrl);
    }

    private function runChild(int $index): void
    {
        pcntl_signal(SIGINT, function () {
            posix_kill($this->parentPid, SIGINT);
        });

        $this->logger->info("Child $index start");

        $client = new Client();
        $redis = new Redis();
        $db = new Database();

        $linkService = new LinkService($redis, $this->logger, $client, $this->baseUrl);
        $dataService = new DataService($db, $this->logger);
        $documentService = new DocumentService($client, $this->logger);

        $job = new Job($this->logger, $linkService, $dataService, $documentService);
        $job->handle();
    }

    public function stop(): void
    {
        if ($this->isStopped) {
            return;
        }

        $this->isStopped = true;

        if (getmypid() !== $this->parentPid) {
            return;
        }

        $this->logger->warning("Stopping parser...");

        foreach ($this->pids as $pid) {
            if (posix_kill($pid, 0)) {
                posix_kill($pid, SIGTERM);
                $this->logger->warning("Stopping child $pid");
            } else {
                $this->logger->info("Child $pid already exited.");
            }
        }
    }

    private function waitChildren(): void
    {
        while (true) {
            $pid = pcntl_wait($status, WNOHANG);

            if ($pid > 0) {
                $this->logger->info("Child $pid exited");
            } elseif ($pid === 0) {
                usleep(50000);
            } elseif ($pid === -1) {
                $this->logger->info("All child processes have finished.");
                break;
            }
        }
    }
}
