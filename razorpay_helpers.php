<?php
require_once __DIR__ . '/db.php';

function razorpayConfig()
{
    return require __DIR__ . '/razorpay_config.php';
}

function razorpayConfigured()
{
    $config = razorpayConfig();
    return $config['key_id'] !== 'YOUR_RAZORPAY_KEY_ID'
        && $config['key_secret'] !== 'YOUR_RAZORPAY_KEY_SECRET'
        && trim($config['key_id']) !== ''
        && trim($config['key_secret']) !== '';
}

function razorpayJsonResponse(array $payload, int $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function getBookingById(mysqli $conn, int $bookingId)
{
    $stmt = $conn->prepare('SELECT * FROM bookings WHERE id = ?');
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

function callRazorpayApi(string $method, string $path, ?array $payload = null)
{
    $config = razorpayConfig();
    $url = 'https://api.razorpay.com/v1/' . ltrim($path, '/');

    $ch = curl_init($url);
    $headers = ['Content-Type: application/json'];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $config['key_id'] . ':' . $config['key_secret'],
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [false, ['error' => ['description' => $curlError ?: 'Razorpay request failed.']], $httpCode];
    }

    $decoded = json_decode($response, true);
    if ($httpCode >= 400) {
        return [false, $decoded ?: ['error' => ['description' => 'Razorpay API error.']], $httpCode];
    }

    return [true, $decoded ?: [], $httpCode];
}

function findPaymentRowByOrderId(mysqli $conn, string $orderId)
{
    $stmt = $conn->prepare('SELECT * FROM payments WHERE gateway_order_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('s', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

function findLatestPaymentRowByBookingId(mysqli $conn, int $bookingId)
{
    $stmt = $conn->prepare('SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

function upsertPendingGatewayPayment(mysqli $conn, array $booking, string $orderId, string $receipt)
{
    $existing = findPaymentRowByOrderId($conn, $orderId);

    $name = $booking['name'];
    $phone = $booking['phone'];
    $movie = $booking['movie'];
    $theater = $booking['theater'] ?? 'T1 - 4K';
    $seats = $booking['seats'];
    $totalAmount = (float) $booking['totalAmount'];
    $bookingId = (int) $booking['id'];
    $time = date('Y-m-d H:i:s');
    $paymentMethod = 'upi';
    $status = 'created';
    $gateway = 'razorpay';

    if ($existing) {
        $stmt = $conn->prepare('UPDATE payments SET booking_id=?, name=?, phone=?, movie=?, theater=?, seats=?, totalAmount=?, cash_given=?, balance=?, time=?, payment_method=?, payment_status=?, gateway=?, reference_note=? WHERE id=?');
        $cashGiven = 0.00;
        $balance = $totalAmount;
        $id = (int) $existing['id'];
        $stmt->bind_param(
            'isssssdddsssssi',
            $bookingId,
            $name,
            $phone,
            $movie,
            $theater,
            $seats,
            $totalAmount,
            $cashGiven,
            $balance,
            $time,
            $paymentMethod,
            $status,
            $gateway,
            $receipt,
            $id
        );
        $stmt->execute();
        $stmt->close();
        return $id;
    }

    $stmt = $conn->prepare('INSERT INTO payments (booking_id, name, phone, movie, theater, seats, totalAmount, cash_given, balance, time, payment_method, payment_status, gateway, gateway_order_id, reference_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $cashGiven = 0.00;
    $balance = $totalAmount;
    $stmt->bind_param(
        'isssssdddssssss',
        $bookingId,
        $name,
        $phone,
        $movie,
        $theater,
        $seats,
        $totalAmount,
        $cashGiven,
        $balance,
        $time,
        $paymentMethod,
        $status,
        $gateway,
        $orderId,
        $receipt
    );
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function markGatewayPaymentSuccess(mysqli $conn, array $booking, string $orderId, string $paymentId, string $signature)
{
    $existing = findPaymentRowByOrderId($conn, $orderId);
    $bookingId = (int) $booking['id'];
    $name = $booking['name'];
    $phone = $booking['phone'];
    $movie = $booking['movie'];
    $theater = $booking['theater'] ?? 'T1 - 4K';
    $seats = $booking['seats'];
    $totalAmount = (float) $booking['totalAmount'];
    $time = date('Y-m-d H:i:s');
    $paymentMethod = 'upi';
    $paymentStatus = 'paid';
    $gateway = 'razorpay';
    $cashGiven = $totalAmount;
    $balance = 0.00;

    if ($existing) {
        $stmt = $conn->prepare('UPDATE payments SET booking_id=?, name=?, phone=?, movie=?, theater=?, seats=?, totalAmount=?, cash_given=?, balance=?, time=?, payment_method=?, payment_status=?, gateway=?, gateway_payment_id=?, gateway_signature=? WHERE id=?');
        $id = (int) $existing['id'];
        $stmt->bind_param(
            'isssssdddssssssi',
            $bookingId,
            $name,
            $phone,
            $movie,
            $theater,
            $seats,
            $totalAmount,
            $cashGiven,
            $balance,
            $time,
            $paymentMethod,
            $paymentStatus,
            $gateway,
            $paymentId,
            $signature,
            $id
        );
        $stmt->execute();
        $stmt->close();
        return $id;
    }

    $stmt = $conn->prepare('INSERT INTO payments (booking_id, name, phone, movie, theater, seats, totalAmount, cash_given, balance, time, payment_method, payment_status, gateway, gateway_order_id, gateway_payment_id, gateway_signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param(
        'isssssdddsssssss',
        $bookingId,
        $name,
        $phone,
        $movie,
        $theater,
        $seats,
        $totalAmount,
        $cashGiven,
        $balance,
        $time,
        $paymentMethod,
        $paymentStatus,
        $gateway,
        $orderId,
        $paymentId,
        $signature
    );
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function markGatewayPaymentFailed(mysqli $conn, int $bookingId, string $orderId, string $reason = 'Payment failed')
{
    $existing = findPaymentRowByOrderId($conn, $orderId);
    if ($existing) {
        $stmt = $conn->prepare('UPDATE payments SET payment_status=?, reference_note=? WHERE id=?');
        $status = 'failed';
        $id = (int) $existing['id'];
        $stmt->bind_param('ssi', $status, $reason, $id);
        $stmt->execute();
        $stmt->close();
        return;
    }

    $booking = getBookingById($conn, $bookingId);
    if (!$booking) {
        return;
    }

    $stmt = $conn->prepare('INSERT INTO payments (booking_id, name, phone, movie, theater, seats, totalAmount, cash_given, balance, time, payment_method, payment_status, gateway, gateway_order_id, reference_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $cashGiven = 0.00;
    $balance = (float) $booking['totalAmount'];
    $time = date('Y-m-d H:i:s');
    $paymentMethod = 'upi';
    $status = 'failed';
    $gateway = 'razorpay';
    $stmt->bind_param(
        'isssssdddssssss',
        $bookingId,
        $booking['name'],
        $booking['phone'],
        $booking['movie'],
        $booking['theater'] ?? 'T1 - 4K',
        $booking['seats'],
        $booking['totalAmount'],
        $cashGiven,
        $balance,
        $time,
        $paymentMethod,
        $status,
        $gateway,
        $orderId,
        $reason
    );
    $stmt->execute();
    $stmt->close();
}

function verifyRazorpaySignature(string $orderId, string $paymentId, string $signature)
{
    $config = razorpayConfig();
    $generated = hash_hmac('sha256', $orderId . '|' . $paymentId, $config['key_secret']);
    return hash_equals($generated, $signature);
}
