<?php
/**
 * FALCONS Theater - Central Configuration
 */

class AppConfig {
    
    // Application Settings
    const APP_NAME = 'FALCONS Theater';
    const APP_VERSION = '2.0';
    const APP_TIMEZONE = 'Asia/Kolkata';
    
    // Site URLs
    public static function getBaseURL() {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                   (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443) ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . dirname($_SERVER['SCRIPT_NAME']);
    }
    
    // Booking Settings
    const SEAT_PRICE = 100;
    const MAX_SEATS_PER_BOOKING = 50;
    const MIN_SEATS_PER_BOOKING = 1;
    const TOTAL_SEATS = 100;
    const BOOKING_EXPIRY_MINUTES = 30;
    
    // Payment Settings
    const MIN_PAYMENT_AMOUNT = 100;
    const MAX_PAYMENT_AMOUNT = 999999.99;
    
    // Session Settings
    const SESSION_TIMEOUT = 3600; // 1 hour
    const SESSION_WARNING = 300;  // 5 minutes before timeout
    
    // Email Settings
    const MAIL_FROM_NAME = 'FALCONS Theater';
    const OTP_VALIDITY_MINUTES = 10;
    const OTP_LENGTH = 6;
    const GOOGLE_CLIENT_ID = 'YOUR_GOOGLE_CLIENT_ID';
    
    // Validation Rules
    const MIN_USERNAME_LENGTH = 3;
    const MAX_USERNAME_LENGTH = 50;
    const MIN_PASSWORD_LENGTH = 6;
    const MAX_PASSWORD_LENGTH = 255;
    const MIN_PHONE_LENGTH = 10;
    const MAX_PHONE_LENGTH = 15;
    
    // Movie Settings
    const MIN_MOVIE_NAME_LENGTH = 2;
    const MAX_MOVIE_NAME_LENGTH = 200;
    const MIN_DESCRIPTION_LENGTH = 10;
    const MAX_DESCRIPTION_LENGTH = 5000;
    
    // Pagination
    const DEFAULT_PAGE_SIZE = 20;
    const MAX_PAGE_SIZE = 100;
    
    // Languages
    const LANGUAGES = ['English', 'Hindi', 'Malayalam', 'Tamil'];
    
    // Ratings
    const MIN_RATING = 1;
    const MAX_RATING = 5;
    
    // Features
    const ENABLE_RAZORPAY = true;
    const ENABLE_CARD_PAYMENT = true;
    const ENABLE_CASH_PAYMENT = true;
    const ENABLE_EMAIL_OTP = true;
    const ENABLE_OTP_FALLBACK = true;
    const ENABLE_LOGGING = true;
    const ENABLE_DEBUG = false;
    
    // File Uploads
    const MAX_UPLOAD_SIZE = 5242880; // 5MB
    const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    
    // Security
    const PASSWORD_HASH_ALGO = PASSWORD_BCRYPT;
    const PASSWORD_HASH_OPTIONS = ['cost' => 11];
    
    /**
     * Get all settings as array
     */
    public static function getAll() {
        $reflection = new ReflectionClass(self::class);
        $constants = $reflection->getConstants();
        return $constants;
    }
    
    /**
     * Get setting by key
     */
    public static function get($key) {
        return constant('self::' . $key) ?? null;
    }
}

// Set timezone
date_default_timezone_set(AppConfig::APP_TIMEZONE);

?>
