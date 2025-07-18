<?php

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('logger');

$config = require __DIR__ . '/config/logging.php';
$logMode = $config['log_channel'];

if ($logMode === 'file') {
    $logger->pushHandler(new StreamHandler(__DIR__ . '/logger.log', Level::Debug));
}

if ($logMode === 'debug') {
    $logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
}

return $logger;
