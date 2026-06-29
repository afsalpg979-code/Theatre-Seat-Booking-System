<?php
include 'db.php';

$movie = trim($_GET['movie'] ?? '');
$theaters = [
    'T1' => 'T1 - 4K',
    'T2' => 'T2 - 2K',
    'T3' => 'T3 - 3D',
    'T4' => 'T4 - 4DX',
];
$theaterCode = trim($_GET['theater'] ?? 'T1');
$showTime = trim($_GET['time'] ?? '');
$booked = [];

if ($movie === '' || $showTime === '' || !isset($theaters[$theaterCode])) {
    echo json_encode(['bookedSeats' => []]);
    exit;
}

$theater = $theaters[$theaterCode];
$stmt = $conn->prepare("SELECT seats FROM bookings WHERE movie = ? AND time = ? AND theater = ?");
$stmt->bind_param('sss', $movie, $showTime, $theater);
$stmt->execute();
$result = $stmt->get_result();

while ($row = mysqli_fetch_assoc($result)) {
    $seats = explode(',', $row['seats']);
    foreach ($seats as $seat) {
        $seatId = intval(trim($seat));
        $booked[] = ['seatId' => $seatId];
    }
}

$stmt->close();

echo json_encode(['bookedSeats' => $booked]);
?>
