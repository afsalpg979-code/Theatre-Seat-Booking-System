<?php
require_once __DIR__ . '/razorpay_helpers.php';

$bookingId = intval($_GET['booking_id'] ?? 0);
$orderId   = trim($_GET['order_id'] ?? '');

if ($bookingId <= 0 && $orderId === '') {
    razorpayJsonResponse([
        'success' => false,
        'error'   => 'Missing payment lookup details.'
    ], 400);
}

/*
| Find existing payment records
*/
$paymentRow = null;

if ($orderId !== '') {
    $paymentRow = findPaymentRowByOrderId($conn, $orderId);
} elseif ($bookingId > 0) {
    $paymentRow = findLatestPaymentRowByBookingId($conn, $bookingId);
}

/*
| Resolve booking ID from payment record
*/
if ($paymentRow && !empty($paymentRow['booking_id'])) {
    $bookingId = intval($paymentRow['booking_id']);
}

/*
| Already marked as paid locally
*/
if ($paymentRow && ($paymentRow['payment_status'] ?? '') === 'paid') {
    razorpayJsonResponse([
        'success'      => true,
        'status'       => 'paid',
        'redirect_url' => 'print1.php?id=' . $bookingId,
    ]);
}

/*
| Check Razorpay directly using the order ID
*/
if ($orderId !== '' && razorpayConfigured()) {

    [$ok, $response] = callRazorpayApi(
        'GET',
        'orders/' . rawurlencode($orderId) . '/payments'
    );

    if ($ok && !empty($response['items']) && is_array($response['items'])) {

        /*
         * Prefer a captured payment.
         */
        $capturedPayment = null;
        $authorizedPayment = null;

        foreach ($response['items'] as $payment) {

            $status = $payment['status'] ?? '';

            if ($status === 'captured') {
                $capturedPayment = $payment;
                break;
            }

            if ($status === 'authorized' && $authorizedPayment === null) {
                $authorizedPayment = $payment;
            }
        }

        /*
         | Captured = successful payment
         */
        if ($capturedPayment) {

            /*
             * If booking ID wasn't supplied, try to resolve it again from
             * the payment/order record.
             */
            if ($bookingId <= 0 && $paymentRow && !empty($paymentRow['booking_id'])) {
                $bookingId = intval($paymentRow['booking_id']);
            }

            if ($bookingId > 0) {
                $booking = getBookingById($conn, $bookingId);

                if ($booking) {
                    markGatewayPaymentSuccess(
                        $conn,
                        $booking,
                        $orderId,
                        $capturedPayment['id'] ?? '',
                        $capturedPayment['acquirer_data']['rrn'] ?? ''
                    );

                    /*
                     * Re-fetch the payment row so the response reflects
                     * the latest database state.
                     */
                    $paymentRow = findPaymentRowByOrderId($conn, $orderId);

                    razorpayJsonResponse([
                        'success'      => true,
                        'status'       => 'paid',
                        'redirect_url' => 'print1.php?id=' . $bookingId,
                        'payment_id'   => $capturedPayment['id'] ?? '',
                    ]);
                }
            }

            /*
             * Payment was captured but we could not resolve the booking.
             */
            razorpayJsonResponse([
                'success' => false,
                'status'  => 'error',
                'error'   => 'Payment was captured, but the booking could not be found.'
            ], 500);
        }

        /*
         | Authorized but not captured.
         * Do NOT immediately mark this as paid. It can still be captured
         * later depending on your Razorpay capture configuration.
         */
        if ($authorizedPayment) {
            razorpayJsonResponse([
                'success'    => true,
                'status'     => 'pending',
                'payment_id' => $authorizedPayment['id'] ?? '',
                'message'    => 'Payment is authorized and awaiting capture.'
            ]);
        }
    }
}

/*
| Locally recorded failed payment
*/
if ($paymentRow && ($paymentRow['payment_status'] ?? '') === 'failed') {

    $message = trim($paymentRow['reference_note'] ?? '');

    if ($message === '') {
        $message = 'Payment failed. Please try again.';
    }

    razorpayJsonResponse([
        'success' => true,
        'status'  => 'failed',
        'message' => $message,
    ]);
}

/*
| Payment still pending
*/
razorpayJsonResponse([
    'success' => true,
    'status'  => 'pending',
]);
