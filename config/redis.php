<?php
return [
    'connection' => [
        'scheme' => $_ENV['REDIS_SCHEME'],
        'host' => $_ENV['REDIS_HOST'],
        'port' => $_ENV['REDIS_PORT'],
        'password' => $_ENV['REDIS_PASSWORD'],
        'database' => $_ENV['REDIS_DATABASE'],
    ],
    'queue' => [
        'links' => 'queue:links',
        'processed' => 'queue:processed',
        'error' => 'queue:errors',
    ]
];
