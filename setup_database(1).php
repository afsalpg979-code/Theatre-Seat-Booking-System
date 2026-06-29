<?php
/**
 * FALCONS Theater - Database Schema Setup
 * Run this file once to set up all required tables
 */

include 'db.php';

$tables = [
    // Bookings table
    "CREATE TABLE IF NOT EXISTS `bookings` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `seats` TEXT NOT NULL,
        `movie` VARCHAR(100) NOT NULL,
        `theater` VARCHAR(20) NOT NULL DEFAULT 'T1 - 4K',
        `totalAmount` DECIMAL(10, 2) NOT NULL,
        `time` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_phone` (`phone`),
        INDEX `idx_movie` (`movie`),
        INDEX `idx_time` (`time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Payments table
    "CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `booking_id` INT(11),
        `name` VARCHAR(100),
        `phone` VARCHAR(20),
        `movie` VARCHAR(100),
        `theater` VARCHAR(20) NOT NULL DEFAULT 'T1 - 4K',
        `seats` TEXT,
        `totalAmount` DECIMAL(10, 2),
        `cash_given` DECIMAL(10, 2),
        `balance` DECIMAL(10, 2),
        `payment_method` VARCHAR(50) DEFAULT 'cash',
        `payment_status` VARCHAR(50) DEFAULT 'pending',
        `gateway` VARCHAR(50),
        `gateway_order_id` VARCHAR(255),
        `gateway_payment_id` VARCHAR(255),
        `gateway_signature` VARCHAR(255),
        `reference_note` VARCHAR(500),
        `time` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL,
        INDEX `idx_booking_id` (`booking_id`),
        INDEX `idx_payment_status` (`payment_status`),
        INDEX `idx_gateway_order_id` (`gateway_order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Clients (Users) table
    "CREATE TABLE IF NOT EXISTS `clients` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(20),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `last_login` DATETIME,
        INDEX `idx_email` (`email`),
        INDEX `idx_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Admin table
    "CREATE TABLE IF NOT EXISTS `admin` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `email` VARCHAR(100),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `last_login` DATETIME,
        INDEX `idx_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Upcoming movies table
    "CREATE TABLE IF NOT EXISTS `upcoming_movies` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(200) NOT NULL,
        `language` VARCHAR(50),
        `image` VARCHAR(500),
        `trailer_url` TEXT,
        `rating` DECIMAL(3, 1),
        `votes` INT(11) DEFAULT 0,
        `description` TEXT,
        `release_date` DATE,
        `ticket_price` DECIMAL(10, 2) NOT NULL DEFAULT 100.00,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_release_date` (`release_date`),
        INDEX `idx_language` (`language`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Ratings table
    "CREATE TABLE IF NOT EXISTS `ratings` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `movie_id` INT(11),
        `user_id` INT(11),
        `stars` INT(1) CHECK (`stars` >= 1 AND `stars` <= 5),
        `comment` TEXT,
        `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
        INDEX `idx_movie_id` (`movie_id`),
        INDEX `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Contact messages table
    "CREATE TABLE IF NOT EXISTS `messages` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `subject` VARCHAR(200),
        `message` TEXT NOT NULL,
        `status` VARCHAR(50) DEFAULT 'unread',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `read_at` DATETIME,
        INDEX `idx_email` (`email`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

$successCount = 0;
$errors = [];

foreach ($tables as $sql) {
    if (mysqli_query($conn, $sql)) {
        $successCount++;
    } else {
        $errors[] = mysqli_error($conn);
    }
}

// Insert default admin if not exists
$adminCheck = mysqli_query($conn, "SELECT * FROM admin WHERE username = 'AFSALPG' LIMIT 1");

if (mysqli_num_rows($adminCheck) === 0) {
    $adminPassword = password_hash('560396', PASSWORD_BCRYPT);
    $adminSql = "INSERT INTO admin (username, password, email) VALUES ('AFSALPG', '$adminPassword', 'admin@falconstheater.com')";
    
    if (mysqli_query($conn, $adminSql)) {
        $successCount++;
    } else {
        $errors[] = 'Failed to insert default admin: ' . mysqli_error($conn);
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>FALCONS Theater - Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            margin-top: 20px;
        }
        .button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>FALCONS Theater - Database Setup</h1>
        
        <div class="info">
            <strong>Setup Status:</strong> Database schema initialization
        </div>
        
        <?php if ($successCount > 0): ?>
            <div class="success">
                <strong>✓ Success!</strong> Created <?= $successCount ?> database tables.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <strong>Errors encountered:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="info">
            <strong>Database Information:</strong>
            <ul>
                <li><strong>Tables Created:</strong> bookings, payments, clients, admin, upcoming_movies, ratings, messages</li>
                <li><strong>Default Admin:</strong> Username: AFSALPG | Password: 560396</li>
                <li><strong>Database:</strong> cinema_db</li>
            </ul>
        </div>
        
        <a href="index.php" class="button">Go to Home Page</a>
    </div>
</body>
</html>
