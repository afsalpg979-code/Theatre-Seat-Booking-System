<?php
/**
 * FALCONS Theater - Application Bootstrap
 * Include this file at the beginning of every PHP file.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Harden the session before session_start().
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Regenerate a session ID periodically to reduce fixation risk while preserving data.
$now = time();
if (!isset($_SESSION['_security_session_started'])) {
    $_SESSION['_security_session_started'] = $now;
} elseif ($now - (int)$_SESSION['_security_session_started'] >= 900) {
    session_regenerate_id(true);
    $_SESSION['_security_session_started'] = $now;
}

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

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

require_once BASE_PATH . '/app_config.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/utils.php';
require_once BASE_PATH . '/validation.php';
require_once BASE_PATH . '/logger.php';
require_once BASE_PATH . '/mailer.php';

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

if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0755, true);
}
?>
