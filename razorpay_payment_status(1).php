<?php
require_once __DIR__ . '/razorpay_helpers.php';

$bookingId = intval($_GET['booking_id'] ?? 0);
$orderId = trim($_GET['order_id'] ?? '');

if ($bookingId <= 0 && $orderId === '') {
    razorpayJsonResponse(['success' => false, 'error' => 'Missing payment lookup details.'], 400);
}

$paymentRow = null;
if ($orderId !== '') {
    $paymentRow = findPaymentRowByOrderId($conn, $orderId);
} elseif ($bookingId > 0) {
    $paymentRow = findLatestPaymentRowByBookingId($conn, $bookingId);
}

if ($paymentRow && ($paymentRow['payment_status'] ?? '') === 'paid') {
    razorpayJsonResponse([
        'success' => true,
        'status' => 'paid',
        'redirect_url' => 'print1.php?id=' . intval($paymentRow['booking_id'] ?: $bookingId),
    ]);
}

if ($orderId !== '' && razorpayConfigured()) {
    [$ok, $response] = callRazorpayApi('GET', 'orders/' . rawurlencode($orderId) . '/payments');
    if ($ok && !empty($response['items'])) {
        foreach ($response['items'] as $payment) {
            if (($payment['status'] ?? '') === 'captured' || ($payment['status'] ?? '') === 'authorized') {
                $booking = getBookingById($conn, $bookingId);
                if ($booking) {
                    markGatewayPaymentSuccess(
                        $conn,
                        $booking,
                        $orderId,
                        $payment['id'] ?? '',
                        $payment['acquirer_data']['rrn'] ?? ''
                    );
                }
                razorpayJsonResponse([
                    'success' => true,
                    'status' => 'paid',
                    'redirect_url' => 'print1.php?id=' . $bookingId,
                ]);
            }
        }
    }
}

if ($paymentRow && ($paymentRow['payment_status'] ?? '') === 'failed') {
    razorpayJsonResponse([
        'success' => true,
        'status' => 'failed',
        'message' => $paymentRow['reference_note'] ?: 'Payment failed.',
    ]);
}

razorpayJsonResponse([
    'success' => true,
    'status' => 'pending',
]);
