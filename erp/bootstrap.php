<?php
declare(strict_types=1);
$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['app']['timezone']);
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',$config['db']['host'],$config['db']['port'],$config['db']['name'],$config['db']['charset']);
try {
    $pdo = new PDO($dsn,$config['db']['user'],$config['db']['password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('ERP database connection failed.');
}
