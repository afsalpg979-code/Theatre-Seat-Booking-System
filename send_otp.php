<?php
session_start();
require_once 'vendor/autoload.php';
require_once __DIR__ . '/otp_rate_limit.php';

use Twilio\Rest\Client;

$account_sid = getenv('TWILIO_ACCOUNT_SID') ?: 'YOUR_TWILIO_SID';
$auth_token = getenv('TWILIO_AUTH_TOKEN') ?: 'YOUR_TWILIO_AUTH_TOKEN';
$twilio_number = getenv('TWILIO_WHATSAPP_FROM') ?: 'whatsapp:+14155238886';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$phone = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
if ($phone === '' || strlen($phone) < 10 || strlen($phone) > 15) {
    http_response_code(400);
    exit('Invalid request.');
}

// Prevent OTP flooding: max 3 sends per phone/IP in 15 minutes and 5 per IP in 15 minutes.
[$phoneAllowed, $phoneRetry] = otp_limit_check('otp_send_phone', $phone, 3, 900);
[$ipAllowed, $ipRetry] = otp_limit_check('otp_send_ip', 'ip', 5, 900);
if (!$phoneAllowed || !$ipAllowed) {
    http_response_code(429);
    header('Retry-After: ' . max($phoneRetry, $ipRetry));
    exit('Too many OTP requests. Please try again later.');
}

if (isset($_SESSION['otp_issued_at']) && (time() - (int)$_SESSION['otp_issued_at']) < 60) {
    http_response_code(429);
    header('Retry-After: ' . (60 - (time() - (int)$_SESSION['otp_issued_at'])));
    exit('Please wait before requesting another OTP.');
}

$_SESSION['register_data'] = $_POST;
$_SESSION['register_data']['phone'] = $phone;

$otp = random_int(100000, 999999);
$_SESSION['otp'] = password_hash((string)$otp, PASSWORD_DEFAULT);
$_SESSION['otp_issued_at'] = time();
$_SESSION['otp_expires_at'] = time() + 600;
$_SESSION['otp_attempts'] = 0;
$_SESSION['otp_phone'] = hash('sha256', $phone);

try {
    $client = new Client($account_sid, $auth_token);
    $client->messages->create(
        'whatsapp:+' . $phone,
        [
            'from' => $twilio_number,
            'body' => 'Your FALCONS THEATER OTP is: ' . $otp
        ]
    );
    otp_limit_increment('otp_send_phone', $phone, 900);
    otp_limit_increment('otp_send_ip', 'ip', 900);
} catch (Throwable $e) {
    unset($_SESSION['otp'], $_SESSION['otp_expires_at']);
    http_response_code(502);
    exit('Unable to send OTP. Please try again later.');
}

header('Location: verify_otp.php');
exit();
?>
