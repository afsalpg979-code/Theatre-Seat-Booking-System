<?php
// Reference implementation for the booking API. Replace save-booking.php with
// this logic after testing against the exact frontend payload.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST request required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$name = trim((string)($data['name'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
$movie = trim((string)($data['movie'] ?? ''));
$time = trim((string)($data['time'] ?? ''));
$theaters = ['T1' => 'T1 - 4K', 'T2' => 'T2 - 2K', 'T3' => 'T3 - 3D', 'T4' => 'T4 - 4DX'];
$theaterCode = trim((string)($data['theater'] ?? 'T1'));
$selectedSeats = $data['seats'] ?? [];

if ($name === '' || mb_strlen($name) > 100 || !preg_match('/^[0-9+()\- ]{7,20}$/', $phone) || $movie === '' || mb_strlen($movie) > 100 || !isset($theaters[$theaterCode]) || !is_array($selectedSeats) || count($selectedSeats) < 1 || count($selectedSeats) > 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid booking data']);
    exit;
}

$seats = [];
foreach ($selectedSeats as $seat) {
    $seatId = filter_var($seat, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 160]]);
    if ($seatId === false) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid seat selection']);
        exit;
    }
    $seats[] = (int)$seatId;
}
$seats = array_values(array_unique($seats));

$dt = DateTime::createFromFormat('Y-m-d H:i:s', $time);
if (!$dt || $dt->format('Y-m-d H:i:s') !== $time) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid show time']);
    exit;
}

$theater = $theaters[$theaterCode];

try {
    $ticketPrice = 100.00;
    $priceStmt = $conn->prepare('SELECT ticket_price FROM upcoming_movies WHERE title = ? ORDER BY release_date DESC LIMIT 1');
    $priceStmt->bind_param('s', $movie);
    $priceStmt->execute();
    $priceResult = $priceStmt->get_result();
    if ($row = $priceResult->fetch_assoc()) {
        $ticketPrice = max(10.00, (float)$row['ticket_price']);
    }
    $priceStmt->close();

    $totalAmount = 0.0;
    foreach ($seats as $seatId) {
        $totalAmount += $seatId <= 80 ? $ticketPrice : ($seatId <= 130 ? $ticketPrice * 1.5 : $ticketPrice * 2);
    }

    $conn->begin_transaction();
    $check = $conn->prepare('SELECT seats FROM bookings WHERE movie = ? AND time = ? AND theater = ? FOR UPDATE');
    $check->bind_param('sss', $movie, $time, $theater);
    $check->execute();
    $result = $check->get_result();
    $booked = [];
    while ($row = $result->fetch_assoc()) {
        foreach (explode(',', (string)$row['seats']) as $seat) {
            $id = (int)trim($seat);
            if ($id > 0) $booked[$id] = true;
        }
    }
    $check->close();

    foreach ($seats as $seatId) {
        if (isset($booked[$seatId])) {
            $conn->rollback();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => "Seat $seatId is already booked"]);
            exit;
        }
    }

    $seatCsv = implode(',', $seats);
    $stmt = $conn->prepare('INSERT INTO bookings (name, phone, seats, movie, theater, totalAmount, time) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssssds', $name, $phone, $seatCsv, $movie, $theater, $totalAmount, $time);
    $stmt->execute();
    $bookingId = $stmt->insert_id;
    $stmt->close();
    $conn->commit();

    echo json_encode(['success' => true, 'booking_id' => $bookingId, 'totalAmount' => $totalAmount]);
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('Booking save error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to save booking. Please try again.']);
}
?>
