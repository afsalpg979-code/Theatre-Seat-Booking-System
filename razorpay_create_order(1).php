<?php
require_once __DIR__ . '/razorpay_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    razorpayJsonResponse(['success' => false, 'error' => 'Invalid request method.'], 405);
}

if (!razorpayConfigured()) {
    razorpayJsonResponse(['success' => false, 'error' => 'Razorpay keys are not configured yet.'], 400);
}

$data = json_decode(file_get_contents('php://input'), true);
$bookingId = intval($data['booking_id'] ?? 0);

if ($bookingId <= 0) {
    razorpayJsonResponse(['success' => false, 'error' => 'Invalid booking ID.'], 400);
}

$booking = getBookingById($conn, $bookingId);
if (!$booking) {
    razorpayJsonResponse(['success' => false, 'error' => 'Booking not found.'], 404);
}

$config = razorpayConfig();
$amountPaise = (int) round(((float) $booking['totalAmount']) * 100);
$receipt = 'booking_' . $bookingId . '_' . time();

[$ok, $response] = callRazorpayApi('POST', 'orders', [
    'amount' => $amountPaise,
    'currency' => $config['currency'],
    'receipt' => $receipt,
    'notes' => [
        'booking_id' => (string) $bookingId,
        'movie' => (string) $booking['movie'],
        'customer' => (string) $booking['name'],
    ],
]);

if (!$ok) {
    $error = $response['error']['description'] ?? 'Unable to create Razorpay order.';
    razorpayJsonResponse(['success' => false, 'error' => $error], 500);
}

upsertPendingGatewayPayment($conn, $booking, $response['id'], $receipt);

razorpayJsonResponse([
    'success' => true,
    'order_id' => $response['id'],
    'amount' => $response['amount'],
    'currency' => $response['currency'],
    'booking_id' => $bookingId,
    'key_id' => $config['key_id'],
    'name' => $config['company_name'],
    'description' => 'Booking payment for ' . $booking['movie'],
    'customer_name' => $booking['name'],
    'customer_phone' => $booking['phone'],
    'theme_color' => $config['theme_color'],
]);
