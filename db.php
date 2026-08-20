<?php
/**
 * FALCONS Theater - central database connection.
 * Schema changes are handled by cinema_db.sql / setup_database.php,
 * not on every page request.
 */

$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'cinema_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $password, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('FALCONS Theater DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Please check XAMPP/MySQL and cinema_db.');
}

date_default_timezone_set('Asia/Kolkata');
?>
