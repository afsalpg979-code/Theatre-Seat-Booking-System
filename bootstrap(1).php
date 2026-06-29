<?php
/**
 * FALCONS Theater - Application Bootstrap
 * Include this file at the beginning of every PHP file
 * Example: require_once __DIR__ . '/bootstrap.php';
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Define base path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// Include essential files
require_once BASE_PATH . '/app_config.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/utils.php';
require_once BASE_PATH . '/validation.php';
require_once BASE_PATH . '/logger.php';
require_once BASE_PATH . '/mailer.php';

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "Error: $errstr in $errfile:$errline";
    }
    
    if (DEBUG_MODE || (class_exists('AppConfig') && AppConfig::ENABLE_LOGGING)) {
        Logger::error($errstr, [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ]);
    }
    
    return true;
});

// Set exception handler
set_exception_handler(function($exception) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "Exception: " . $exception->getMessage();
    }
    
    Logger::error($exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
});

// Create logs directory if needed
if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0755, true);
}

?>
