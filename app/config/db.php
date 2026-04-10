<?php

return [
    'class' => 'yii\\db\\Connection',
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_NAME') ?: 'yii2basic'
    ),
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
