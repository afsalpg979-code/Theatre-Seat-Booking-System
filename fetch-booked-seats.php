<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$movie = trim((string)($_GET['movie'] ?? ''));
$theaters = ['T1' => 'T1 - 4K', 'T2' => 'T2 - 2K', 'T3' => 'T3 - 3D', 'T4' => 'T4 - 4DX'];
$theaterCode = trim((string)($_GET['theater'] ?? 'T1'));
$showTime = trim((string)($_GET['time'] ?? ''));

if ($movie === '' || $showTime === '' || !isset($theaters[$theaterCode])) {
    echo json_encode(['bookedSeats' => []]);
    exit;
}

try {
    $theater = $theaters[$theaterCode];
    $stmt = $conn->prepare('SELECT seats FROM bookings WHERE movie = ? AND time = ? AND theater = ?');
    $stmt->bind_param('sss', $movie, $showTime, $theater);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked = [];
    $seen = [];
    while ($row = $result->fetch_assoc()) {
        foreach (explode(',', (string)$row['seats']) as $seat) {
            $seatId = (int)trim($seat);
            if ($seatId >= 1 && $seatId <= 160 && !isset($seen[$seatId])) {
                $seen[$seatId] = true;
                $booked[] = ['seatId' => $seatId];
            }
        }
    }
    $stmt->close();
    echo json_encode(['bookedSeats' => $booked]);
} catch (Throwable $e) {
    error_log('Booked-seat lookup error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['bookedSeats' => [], 'error' => 'Unable to load seat availability']);
}
?>
