<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/otp_rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$enteredOtp = trim((string)($_POST['otp'] ?? ''));
$storedOtp = (string)($_SESSION['otp'] ?? '');
$phoneHash = (string)($_SESSION['otp_phone'] ?? '');

if (!preg_match('/^\d{6}$/', $enteredOtp) || $storedOtp === '' || $phoneHash === '') {
    http_response_code(400);
    exit('Invalid OTP request.');
}

$sessionAttempts = (int)($_SESSION['otp_attempts'] ?? 0);
if ($sessionAttempts >= 5) {
    http_response_code(429);
    exit('Too many OTP attempts. Please request a new OTP.');
}

[$allowed, $retryAfter] = otp_limit_check('otp_verify', $phoneHash, 10, 900);
if (!$allowed) {
    http_response_code(429);
    header('Retry-After: ' . $retryAfter);
    exit('Too many OTP attempts. Please try again later.');
}

if (time() > (int)($_SESSION['otp_expires_at'] ?? 0)) {
    unset($_SESSION['otp'], $_SESSION['otp_issued_at'], $_SESSION['otp_expires_at'], $_SESSION['otp_attempts'], $_SESSION['otp_phone']);
    exit('OTP expired. Please request a new OTP.');
}

$_SESSION['otp_attempts'] = $sessionAttempts + 1;
otp_limit_increment('otp_verify', $phoneHash, 900);

if (!password_verify($enteredOtp, $storedOtp)) {
    exit('Invalid OTP. Please try again.');
}

$data = $_SESSION['register_data'] ?? null;
if (!is_array($data)) {
    exit('Registration session expired. Please start again.');
}

$conn = new mysqli('localhost', 'root', '', 'cinema_db');
if ($conn->connect_error) {
    http_response_code(500);
    exit('Unable to complete registration.');
}
$conn->set_charset('utf8mb4');

$password = (string)($data['password'] ?? '');
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO clients (username, email, password, age, married_status, phone, address, place, pincode, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) {
    $conn->close();
    http_response_code(500);
    exit('Unable to complete registration.');
}

$stmt->bind_param('ssssssssss', $data['username'], $data['email'], $passwordHash, $data['age'], $data['married_status'], $data['phone'], $data['address'], $data['place'], $data['pincode'], $data['gender']);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    http_response_code(500);
    exit('Unable to complete registration.');
}

$stmt->close();
$conn->close();
otp_limit_reset('otp_verify', $phoneHash);
unset($_SESSION['otp'], $_SESSION['otp_issued_at'], $_SESSION['otp_expires_at'], $_SESSION['otp_attempts'], $_SESSION['otp_phone'], $_SESSION['register_data']);
session_regenerate_id(true);
?>
<h3>Registration successful!</h3>
