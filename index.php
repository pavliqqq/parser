<?php

use App\Fork\ForkManager;
use Dotenv\Dotenv;

set_time_limit(0);
ini_set('memory_limit', -1);

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$forks = new ForkManager();
$forks->run();
