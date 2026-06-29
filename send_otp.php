<?php
session_start();
require_once 'vendor/autoload.php';

use Twilio\Rest\Client;

$account_sid = 'YOUR_TWILIO_SID';
$auth_token = 'YOUR_TWILIO_AUTH_TOKEN';
$twilio_number = 'whatsapp:+14155238886'; // Sandbox number

$client = new Client($account_sid, $auth_token);

// Get form data
$_SESSION['register_data'] = $_POST;
$phone = $_POST['phone'];

// Generate OTP
$otp = rand(100000, 999999);
$_SESSION['otp'] = $otp;

// Send OTP via WhatsApp
$message = $client->messages->create(
    "whatsapp:+" . $phone,
    [
        "from" => $twilio_number,
        "body" => "Your FALCONS THEATER OTP is: $otp"
    ]
);

// Redirect to OTP verify page
header("Location: verify_otp.php");
exit();
?>
