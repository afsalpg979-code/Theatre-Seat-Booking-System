<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "cinema_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Kolkata');

// Run cleanup only if the upcoming_movies table exists.
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'upcoming_movies'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
    $price_column_check = mysqli_query($conn, "SHOW COLUMNS FROM upcoming_movies LIKE 'ticket_price'");
    if ($price_column_check && mysqli_num_rows($price_column_check) === 0) {
        mysqli_query($conn, "ALTER TABLE upcoming_movies ADD ticket_price DECIMAL(10, 2) NOT NULL DEFAULT 100.00 AFTER release_date");
    }

    $trailer_column_check = mysqli_query($conn, "SHOW COLUMNS FROM upcoming_movies LIKE 'trailer_url'");
    if ($trailer_column_check && mysqli_num_rows($trailer_column_check) === 0) {
        mysqli_query($conn, "ALTER TABLE upcoming_movies ADD trailer_url TEXT DEFAULT NULL AFTER image");
    }

    $cleanup_sql = "DELETE FROM upcoming_movies WHERE release_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    mysqli_query($conn, $cleanup_sql);
}
?>
