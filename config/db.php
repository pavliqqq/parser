<?php
return [
    'connection' => [
        'host' => $_ENV['DB_HOST'],
        'dbname' => $_ENV['DB_NAME'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD'],
    ],
    'data_table' => [
        'name' => 'crossword',
    ]
];
