<?php
declare(strict_types=1);
return [
    'db' => [
        'host' => getenv('ERP_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('ERP_DB_PORT') ?: 3306),
        'name' => getenv('ERP_DB_NAME') ?: 'cinema_db',
        'user' => getenv('ERP_DB_USER') ?: 'root',
        'password' => getenv('ERP_DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Smart Theatre ERP',
        'timezone' => getenv('ERP_TIMEZONE') ?: 'Asia/Kolkata',
    ],
];
