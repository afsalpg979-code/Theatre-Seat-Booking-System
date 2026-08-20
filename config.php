<?php
/**
 * FALCONS Theater - application configuration and database connection.
 * Schema changes belong in the SQL/setup files, never in normal page requests.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'cinema_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset('utf8mb4');

    // Backward-compatible table availability check used by older pages.
    // It is read-only and does not modify the schema.
    $table_check = $conn->query("SHOW TABLES LIKE 'upcoming_movies'");
} catch (mysqli_sql_exception $e) {
    error_log('FALCONS Theater database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Please check the database configuration.');
}

date_default_timezone_set('Asia/Kolkata');
?>
