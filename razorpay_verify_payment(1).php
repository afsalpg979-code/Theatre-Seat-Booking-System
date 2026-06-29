<?php
require_once __DIR__ . '/razorpay_helpers.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    razorpayJsonResponse(['success' => false, 'error' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$bookingId = intval($data['booking_id'] ?? 0);
$orderId = trim($data['razorpay_order_id'] ?? '');
$paymentId = trim($data['razorpay_payment_id'] ?? '');
$signature = trim($data['razorpay_signature'] ?? '');

if ($bookingId <= 0 || $orderId === '' || $paymentId === '' || $signature === '') {
    razorpayJsonResponse(['success' => false, 'error' => 'Missing payment verification details.'], 400);
}

$booking = getBookingById($conn, $bookingId);
if (!$booking) {
    razorpayJsonResponse(['success' => false, 'error' => 'Booking not found.'], 404);
}

if (!verifyRazorpaySignature($orderId, $paymentId, $signature)) {
    markGatewayPaymentFailed($conn, $bookingId, $orderId, 'Signature verification failed');
    razorpayJsonResponse(['success' => false, 'error' => 'Payment verification failed.'], 400);
}

markGatewayPaymentSuccess($conn, $booking, $orderId, $paymentId, $signature);

// Send ticket confirmation email to customer
$recipientEmail = resolveLoggedInUserEmail($conn);
if ($recipientEmail) {
    $ticketUrl = buildTicketAbsoluteUrl($bookingId);
    $emailNote = '';
    sendTicketMail($recipientEmail, $booking, $ticketUrl, $emailNote);
}

razorpayJsonResponse([
    'success' => true,
    'status' => 'paid',
    'redirect_url' => 'print1.php?id=' . $bookingId,
]);
