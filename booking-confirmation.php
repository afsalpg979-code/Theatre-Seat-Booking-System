<?php
include 'db.php';
require_once __DIR__ . '/utils.php';

$movieName = isset($_GET['movie']) && trim($_GET['movie']) !== ''
    ? sanitizeInput($_GET['movie'])
    : 'FALCONS Theater';
$today = date('Y-m-d');
$showTimes = [
    $today . ' 10:00:00' => '10:00 AM',
    $today . ' 13:00:00' => '01:00 PM',
    $today . ' 16:00:00' => '04:00 PM',
    $today . ' 19:00:00' => '07:00 PM',
    $today . ' 22:00:00' => '10:00 PM',
];
$selectedShowTime = isset($_GET['time'], $showTimes[$_GET['time']])
    ? $_GET['time']
    : array_key_first($showTimes);
$theaters = [
    'T1' => 'T1 - 4K',
    'T2' => 'T2 - 2K',
    'T3' => 'T3 - 3D',
    'T4' => 'T4 - 4DX',
];
$selectedTheaterCode = isset($_GET['theater'], $theaters[$_GET['theater']])
    ? $_GET['theater']
    : array_key_first($theaters);
$ticketPrice = 100.00;

// Schema changes must be applied by the database setup/migration process,
// never by a public booking request. The maintained schema includes ticket_price.
$priceStmt = $conn->prepare("SELECT ticket_price FROM upcoming_movies WHERE title = ? ORDER BY release_date DESC LIMIT 1");
if ($priceStmt) {
    $priceStmt->bind_param('s', $movieName);
    $priceStmt->execute();
    $priceResult = $priceStmt->get_result();
    if ($priceRow = $priceResult->fetch_assoc()) {
        $ticketPrice = max(10.00, (float) $priceRow['ticket_price']);
    }
    $priceStmt->close();
}

$seatSections = [
    ['name' => 'Silver', 'start' => 1, 'end' => 80, 'price' => $ticketPrice, 'color' => '#22c55e'],
    ['name' => 'Gold', 'start' => 81, 'end' => 130, 'price' => round($ticketPrice * 1.5, 2), 'color' => '#f3b61f'],
    ['name' => 'VIP', 'start' => 131, 'end' => 160, 'price' => round($ticketPrice * 2, 2), 'color' => '#a855f7'],
];
$seatCount = max(array_column($seatSections, 'end'));

function getSeatSectionPrice($seatId, $seatSections, $fallbackPrice)
{
    foreach ($seatSections as $section) {
        if ($seatId >= $section['start'] && $seatId <= $section['end']) {
            return (float) $section['price'];
        }
    }
    return (float) $fallbackPrice;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        jsonError('Invalid request data.', 400);
    }

    $name = sanitizeInput($data['name'] ?? '');
    $phone = sanitizeInput($data['phone'] ?? '');
    $selectedSeats = $data['seats'] ?? [];
    $movie = sanitizeInput($data['movie'] ?? $movieName);
    $time = isset($data['time']) ? sanitizeInput($data['time']) : '';
    $theaterCode = isset($data['theater']) ? sanitizeInput($data['theater']) : '';

    if ($name === '' || !validatePhone($phone) || !validateMovieName($movie) || !isset($showTimes[$time]) || !isset($theaters[$theaterCode]) || !validateSeatNumbers($selectedSeats)) {
        jsonError('Missing or invalid booking data.', 422);
    }

    $selectedSeats = array_values(array_unique(array_map('intval', $selectedSeats)));
    if (count($selectedSeats) < 1 || count($selectedSeats) > 50) {
        jsonError('Select between 1 and 50 seats.', 422);
    }

    $seats = implode(',', $selectedSeats);
    $theater = $theaters[$theaterCode];
    $totalAmount = 0.0;
    foreach ($selectedSeats as $seatId) {
        $totalAmount += getSeatSectionPrice($seatId, $seatSections, $ticketPrice);
    }

    $conn->begin_transaction();
    try {
        $bookedStmt = $conn->prepare("SELECT seats FROM bookings WHERE movie = ? AND time = ? AND theater = ? FOR UPDATE");
        $bookedStmt->bind_param('sss', $movie, $time, $theater);
        $bookedStmt->execute();
        $bookedResult = $bookedStmt->get_result();
        $alreadyBooked = [];

        while ($bookingRow = $bookedResult->fetch_assoc()) {
            foreach (explode(',', (string) $bookingRow['seats']) as $seat) {
                $seatId = (int) trim($seat);
                if ($seatId > 0) {
                    $alreadyBooked[$seatId] = true;
                }
            }
        }
        $bookedStmt->close();

        foreach ($selectedSeats as $seatId) {
            if (isset($alreadyBooked[$seatId])) {
                $conn->rollback();
                jsonError('One or more selected seats were just booked. Please choose again.', 409);
            }
        }

        $stmt = $conn->prepare("INSERT INTO bookings (name, phone, seats, movie, theater, totalAmount, time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssds', $name, $phone, $seats, $movie, $theater, $totalAmount, $time);
        $stmt->execute();
        $bookingId = $stmt->insert_id;
        $stmt->close();
        $conn->commit();

        echo json_encode(['success' => true, 'booking_id' => $bookingId, 'totalAmount' => $totalAmount]);
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        error_log('Booking confirmation error: ' . $e->getMessage());
        jsonError('Unable to save booking. Please try again.', 500);
    }
    exit;
}

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        http_response_code(400);
        exit('Invalid booking ID.');
    }

    $stmt = $conn->prepare('SELECT id FROM bookings WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        header('Location: buy.php?id=' . (int) $row['id']);
        exit;
    }

    http_response_code(404);
    exit('No booking found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat Booking - <?= htmlspecialchars($movieName, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Poppins',sans-serif;margin:0;padding:0;background:linear-gradient(rgba(0,0,0,.85),rgba(0,0,0,.85)),url('https://images.unsplash.com/photo-1578863579390-9b3bd2170c2d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;background-size:cover;color:#fff}
        h1{text-align:center;padding:30px 20px 10px;font-weight:600;font-size:2rem;color:#FFD700}
        .movie-label{text-align:center;color:#00ffaa;margin-bottom:22px;font-size:1.1rem}
        .showtime-row{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:24px;flex-wrap:wrap}
        .showtime-row label{color:#ffd700;font-weight:700}
        .showtime-row select{min-width:180px;padding:10px 12px;border:1px solid rgba(255,215,0,.45);border-radius:8px;background:rgba(255,255,255,.92);color:#111;font:inherit;font-weight:600}
        .wrapper{max-width:1060px;margin:auto;background:rgba(255,255,255,.05);padding:30px;border-radius:15px;box-shadow:0 0 20px rgba(0,0,0,.5)}
        .seats-container{position:relative;width:100%;height:min(62vh,560px);min-height:420px;margin-bottom:30px;overflow:hidden;border:1px solid rgba(255,215,0,.22);border-radius:12px;background:radial-gradient(circle at 50% 15%,rgba(255,215,0,.18),transparent 32%),linear-gradient(180deg,rgba(7,19,29,.96),rgba(0,0,0,.78));box-shadow:inset 0 0 45px rgba(0,0,0,.6)}
        .seats-container canvas{display:block;width:100%;height:100%}
        .seat-loading{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:24px;color:#fff;text-align:center;background:rgba(0,0,0,.35);z-index:2}
        .seat-legend{display:flex;justify-content:center;gap:14px;margin:-12px 0 22px;flex-wrap:wrap;color:#d8e6f2;font-size:.92rem}
        .legend-item{display:inline-flex;align-items:center;gap:8px}.legend-swatch{width:14px;height:14px;border-radius:4px;border:1px solid rgba(255,255,255,.35)}
        .legend-selected{background:#f59e0b}.legend-booked{background:#8b949e}.selected-seats{min-height:24px;margin:-8px 0 16px;color:#c2d6e5;font-size:.95rem;text-align:center}
        .input-group input{padding:10px;margin:10px;border-radius:8px;border:none;font-size:16px;width:calc(45% - 20px)}
        .actions button,.book-btn{padding:10px 25px;margin:10px 10px 0;font-size:16px;border-radius:8px;border:none;background:#ffd700;color:#000;cursor:pointer;transition:.3s}
        .actions button:hover,.book-btn:hover{background:#ffcc00}.book-btn{display:inline-block;text-decoration:none}.total-amount{font-size:22px;font-weight:bold;margin-bottom:15px;color:#00ffaa}
        @media(max-width:600px){.seats-container{height:430px;min-height:360px}.input-group input{width:100%;margin:8px 0}}
    </style>
</head>
<body>
<h1>Seat Booking</h1>
<div class="movie-label">Movie: <?= htmlspecialchars($movieName, ENT_QUOTES, 'UTF-8') ?></div>
<div class="movie-label">Seat Prices: <?php foreach ($seatSections as $index => $section): ?><?= $index > 0 ? ' | ' : '' ?><?= htmlspecialchars($section['name'], ENT_QUOTES, 'UTF-8') ?> Rs.<?= number_format($section['price'], 2) ?><?php endforeach; ?></div>
<div class="wrapper">
    <div class="showtime-row">
        <label for="theater-select">Theater</label>
        <select id="theater-select">
            <?php foreach ($theaters as $value => $label): ?>
                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $value === $selectedTheaterCode ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <label for="show-time">Show Time</label>
        <select id="show-time">
            <?php foreach ($showTimes as $value => $label): ?>
                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $value === $selectedShowTime ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="seats-container" id="seats-container"><div class="seat-loading" id="seat-loading">Loading 3D theater seats...</div></div>
    <div class="seat-legend" aria-label="Seat legend">
        <?php foreach ($seatSections as $section): ?><span class="legend-item"><span class="legend-swatch" style="background:<?= htmlspecialchars($section['color'], ENT_QUOTES, 'UTF-8') ?>"></span><?= htmlspecialchars($section['name'], ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?>
        <span class="legend-item"><span class="legend-swatch legend-selected"></span>Selected</span><span class="legend-item"><span class="legend-swatch legend-booked"></span>Booked</span>
    </div>
    <div class="selected-seats" id="selected-seats">Selected seats: none</div>
    <div class="total-amount" id="total-amount">Total Amount: Rs.0</div>
    <div class="input-group"><input type="text" id="user-name" maxlength="100" placeholder="Enter your Name"><input type="text" id="user-phone" maxlength="15" inputmode="tel" placeholder="Enter your Phone Number"></div>
    <div class="actions"><button id="clear-selection">Clear Selection</button><button id="confirm-selection">Confirm Selection</button><button id="show-all-booked">Show All Booked</button></div>
    <a href="index.php" class="book-btn">Back to Movie Listings</a>
</div>
<script>
window.theaterBookingConfig={movieName:<?= json_encode($movieName) ?>,selectedTheater:<?= json_encode($selectedTheaterCode) ?>,seatPrice:<?= json_encode($ticketPrice) ?>,seatCount:<?= json_encode($seatCount) ?>,seatSections:<?= json_encode($seatSections) ?>};
</script>
<script type="module" src="theater-seats.js"></script>
</body>
</html>
