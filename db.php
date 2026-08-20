<?php
/**
 * FALCONS Theater - Database Connection
 * Production credentials must be supplied through environment variables.
 */

$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'cinema_db';
$port = (int) (getenv('DB_PORT') ?: 3306);

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    error_log('Database connection failed.');
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}

mysqli_set_charset($conn, 'utf8mb4');

function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition)
{
    $schemaResult = mysqli_query($conn, "SELECT DATABASE()");
    $schemaRow = $schemaResult ? mysqli_fetch_row($schemaResult) : null;
    $schema = mysqli_real_escape_string($conn, (string) ($schemaRow[0] ?? ''));
    $tableSafe = mysqli_real_escape_string($conn, $table);
    $columnSafe = mysqli_real_escape_string($conn, $column);
    $columnCheck = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS column_count
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = '$schema'
           AND TABLE_NAME = '$tableSafe'
           AND COLUMN_NAME = '$columnSafe'"
    );
    $columnRow = $columnCheck ? mysqli_fetch_assoc($columnCheck) : ['column_count' => 0];

    if ((int) ($columnRow['column_count'] ?? 0) === 0) {
        @mysqli_query($conn, "ALTER TABLE `$tableSafe` ADD `$columnSafe` $definition");
    }
}

ensureColumnExists($conn, 'bookings', 'theater', "varchar(20) NOT NULL DEFAULT 'T1 - 4K' AFTER movie");
ensureColumnExists($conn, 'payments', 'transaction_id', "varchar(255) NULL AFTER amount");
