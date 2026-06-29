<?php
/**
 * FALCONS Theater - Error & Activity Logging
 */

class Logger {
    
    private static $logDir = __DIR__ . '/logs';
    
    public static function init() {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    /**
     * Log an error
     */
    public static function error($message, $context = []) {
        self::log('error', $message, $context);
    }
    
    /**
     * Log a warning
     */
    public static function warning($message, $context = []) {
        self::log('warning', $message, $context);
    }
    
    /**
     * Log info
     */
    public static function info($message, $context = []) {
        self::log('info', $message, $context);
    }
    
    /**
     * Log a database query
     */
    public static function query($query, $params = []) {
        self::log('query', 'Database Query', ['query' => $query, 'params' => $params]);
    }
    
    /**
     * Log user activity
     */
    public static function activity($action, $userId = null, $details = []) {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        self::log('activity', $action, array_merge(['user_id' => $userId], $details));
    }
    
    /**
     * Log security events
     */
    public static function security($event, $details = []) {
        self::log('security', $event, array_merge(['ip' => getClientIP()], $details));
    }
    
    /**
     * General logging method
     */
    private static function log($level, $message, $context = []) {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $logFile = self::$logDir . '/' . $level . '_' . date('Y-m-d') . '.log';
        
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get logs from a specific date
     */
    public static function getLogs($level, $date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $logFile = self::$logDir . '/' . $level . '_' . $date . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logs = [];
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        
        foreach ($lines as $line) {
            $logs[] = $line;
        }
        
        return $logs;
    }
    
    /**
     * Clear old logs (older than days)
     */
    public static function cleanup($days = 30) {
        self::init();
        
        $cutoffTime = time() - ($days * 24 * 3600);
        
        if ($handle = opendir(self::$logDir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file !== '.' && $file !== '..') {
                    $filepath = self::$logDir . '/' . $file;
                    if (is_file($filepath) && filemtime($filepath) < $cutoffTime) {
                        unlink($filepath);
                    }
                }
            }
            closedir($handle);
        }
    }
}

// Initialize logger
Logger::init();

?>
