<?php
return [
    'connection' => [
        'host' => $_ENV['DB_HOST'],
        'dbname' => $_ENV['DB_NAME'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD'],
    ],
    'data_table' => [
        'questions' => 'questions',
        'answers' => 'answers',
        'question_answer' => 'question_answer'
    ]
];
