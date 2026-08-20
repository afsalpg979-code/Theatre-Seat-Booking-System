<?php
/**
 * FALCONS Theater - application configuration and database connection.
 * Keep database credentials in this file for the local XAMPP installation.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'cinema_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log('FALCONS Theater database connection failed: ' . $e->getMessage());
    exit('Database connection failed. Please check the database configuration.');
}

date_default_timezone_set('Asia/Kolkata');

// IMPORTANT: Do not delete released movies here. The home page intentionally
// displays movies whose release_date is today or earlier.
// Database schema changes belong in setup_database.php / cinema_db.sql.
?>
