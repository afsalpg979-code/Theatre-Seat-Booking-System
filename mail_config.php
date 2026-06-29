<?php

$mailUsername = getenv('MAIL_USERNAME') ?: 'finuafsal7@gmail.com';
$mailPassword = getenv('MAIL_PASSWORD') ?: 'ezcn qjhy bsdw ohyl';
$mailFromEmail = getenv('MAIL_FROM_EMAIL') ?: $mailUsername;
$mailFromName = getenv('MAIL_FROM_NAME') ?: 'FALCONS Theater';
$mailHost = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
$mailPort = getenv('MAIL_PORT');
$mailSecure = getenv('MAIL_SECURE') ?: 'tls';
$mailTimeout = getenv('MAIL_TIMEOUT');
$mailDebug = getenv('MAIL_DEBUG');

return [
    'host' => $mailHost,
    'port' => $mailPort !== false && $mailPort !== '' ? (int) $mailPort : 587,
    'secure' => $mailSecure,
    'username' => $mailUsername,
    'password' => $mailPassword,
    'from_email' => $mailFromEmail,
    'from_name' => $mailFromName,
    'timeout' => $mailTimeout !== false && $mailTimeout !== '' ? (int) $mailTimeout : 15,
    'debug' => in_array(strtolower((string) $mailDebug), ['1', 'true', 'yes', 'on'], true),
];
