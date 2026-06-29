<?php
/**
 * FALCONS Theater - Utility Functions
 * Centralized helper functions for common operations
 */

// ========== SESSION & SECURITY ==========

function startSecureSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        session_regenerate_id(true);
    }
}

function requireLogin()
{
    startSecureSession();
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdminLogin()
{
    startSecureSession();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: admin_login.php');
        exit;
    }
}

function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function logout()
{
    startSecureSession();
    $_SESSION = [];
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
}

// ========== INPUT VALIDATION & SANITIZATION ==========

function sanitizeInput($input)
{
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function validateEmail($email)
{
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone)
{
    $phone = preg_replace('/\D/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 15 && is_numeric($phone);
}

function validateAmount($amount)
{
    $amount = floatval($amount);
    return $amount > 0 && $amount <= 999999.99;
}

function validateSeatNumbers($seats)
{
    $maxSeatNumber = 160;

    if (!is_array($seats)) {
        return false;
    }
    foreach ($seats as $seat) {
        $seatNum = intval($seat);
        if ($seatNum < 1 || $seatNum > $maxSeatNumber) {
            return false;
        }
    }
    return count($seats) > 0 && count($seats) <= $maxSeatNumber;
}

function validateMovieName($movieName)
{
    $movieName = trim($movieName);
    return strlen($movieName) >= 2 && strlen($movieName) <= 200;
}

// ========== DATABASE HELPERS ==========

function dbEscape(mysqli $conn, $string)
{
    return mysqli_real_escape_string($conn, $string);
}

function dbFetch(mysqli $conn, $query, $params = [], $types = '')
{
    if (empty($params)) {
        $result = mysqli_query($conn, $query);
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return null;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    
    return $row;
}

function dbFetchAll(mysqli $conn, $query, $params = [], $types = '')
{
    if (empty($params)) {
        $result = mysqli_query($conn, $query);
        if (!$result) {
            return [];
        }
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    
    return $rows;
}

function dbInsert(mysqli $conn, $table, $data)
{
    $columns = array_keys($data);
    $values = array_values($data);
    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $columnList = implode(',', $columns);
    
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $query = "INSERT INTO $table ($columnList) VALUES ($placeholders)";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param($types, ...$values);
    $result = $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    
    return $result ? $id : false;
}

function dbUpdate(mysqli $conn, $table, $data, $where, $whereTypes = 's')
{
    $updates = [];
    $types = '';
    $values = [];
    
    foreach ($data as $key => $value) {
        $updates[] = "$key = ?";
        $values[] = $value;
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $updateList = implode(',', $updates);
    $query = "UPDATE $table SET $updateList WHERE id = ?";
    $types .= 'i';
    $values[] = $where;
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param($types, ...$values);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function dbDelete(mysqli $conn, $table, $id)
{
    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param('i', $id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function dbCount(mysqli $conn, $table, $where = '', $params = [])
{
    $query = "SELECT COUNT(*) as count FROM $table";
    if ($where !== '') {
        $query .= " WHERE $where";
    }
    
    if (empty($params)) {
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        return intval($row['count']);
    }
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return 0;
    }
    
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return intval($row['count']);
}

// ========== DATE & TIME FORMATTING ==========

function formatDateTime($dateTime)
{
    if (empty($dateTime) || $dateTime === '0000-00-00 00:00:00') {
        return 'Not available';
    }
    
    try {
        $date = new DateTime($dateTime);
        return $date->format('d M Y, h:i A');
    } catch (Exception $e) {
        return 'Invalid date';
    }
}

function formatDate($date)
{
    if (empty($date)) {
        return 'Not available';
    }
    
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format('d M Y');
    } catch (Exception $e) {
        return 'Invalid date';
    }
}

function formatTime($time)
{
    if (empty($time)) {
        return 'Not available';
    }
    
    try {
        $timeObj = new DateTime($time);
        return $timeObj->format('h:i A');
    } catch (Exception $e) {
        return 'Invalid time';
    }
}

// ========== STRING FORMATTING ==========

function formatCurrency($amount)
{
    return 'Rs ' . number_format($amount, 2);
}

function formatPhoneNumber($phone)
{
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 10) {
        return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    }
    return $phone;
}

function truncateText($text, $length = 100)
{
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

function generateBookingCode($bookingId)
{
    return 'FT' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);
}

// ========== PAGINATION ==========

function getPaginationParams($page = 1, $limit = 20)
{
    $page = max(1, intval($page));
    $limit = max(1, min(100, intval($limit)));
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

function getPaginationLinks($page, $totalPages, $baseUrl)
{
    $links = [];
    
    if ($page > 1) {
        $links['prev'] = $baseUrl . '?page=' . ($page - 1);
    }
    
    if ($page < $totalPages) {
        $links['next'] = $baseUrl . '?page=' . ($page + 1);
    }
    
    return $links;
}

// ========== JSON RESPONSES ==========

function jsonResponse($success, $message = '', $data = [])
{
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function jsonError($message, $code = 400)
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// ========== ERROR HANDLING ==========

function handleException($exception)
{
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return $exception->getMessage();
    }
    
    return 'An error occurred. Please try again.';
}

function setErrorHandler()
{
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        error_log('[' . date('Y-m-d H:i:s') . '] Error: ' . $errstr . ' in ' . $errfile . ':' . $errline);
        return true;
    });
}

// ========== UTILITY HELPERS ==========

function getClientIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ?: 'Unknown';
}

function isAjaxRequest()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function isSecureConnection()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443) ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

function redirectTo($url)
{
    header('Location: ' . $url);
    exit;
}

function redirectWithMessage($url, $message, $type = 'success')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    redirectTo($url);
}

function getFlashMessage()
{
    $message = $_SESSION['flash_message'] ?? '';
    $type = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
    return ['message' => $message, 'type' => $type];
}

function displayFlashMessage()
{
    $flash = getFlashMessage();
    if (!empty($flash['message'])) {
        $class = 'alert-' . htmlspecialchars($flash['type']);
        echo '<div class="alert ' . $class . '">' . htmlspecialchars($flash['message']) . '</div>';
    }
}

// ========== ARRAY HELPERS ==========

function filterArray($array, $keys)
{
    return array_intersect_key($array, array_flip($keys));
}

function extractArray($array, $keys)
{
    $result = [];
    foreach ($keys as $key) {
        if (isset($array[$key])) {
            $result[$key] = $array[$key];
        }
    }
    return $result;
}
?>
