<?php
session_start();
include 'db.php';
require_once __DIR__ . '/mailer.php';

$emailSent = false;
$emailNote = '';

function fetchBooking(mysqli $conn, int $bookingId)
{
    $result = mysqli_query($conn, "SELECT * FROM bookings WHERE id = $bookingId");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }

    return null;
}

function savePayment(mysqli $conn, array $booking, float $amountPaid, float $balance, string $time)
{
    $name = mysqli_real_escape_string($conn, $booking['name']);
    $phone = mysqli_real_escape_string($conn, $booking['phone']);
    $movie = mysqli_real_escape_string($conn, $booking['movie']);
    $theater = mysqli_real_escape_string($conn, $booking['theater'] ?? 'T1 - 4K');
    $seats = mysqli_real_escape_string($conn, $booking['seats']);
    $totalAmount = floatval($booking['totalAmount']);

    $sql = "INSERT INTO payments (name, phone, movie, theater, seats, totalAmount, cash_given, balance, time)
            VALUES ('$name', '$phone', '$movie', '$theater', '$seats', $totalAmount, $amountPaid, $balance, '$time')";

    return mysqli_query($conn, $sql);
}

$paymentSaved = false;
$cashGiven = null;
$balance = null;
$row = null;
$isJsonRequest = stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isJsonRequest) {
    $data = json_decode(file_get_contents('php://input'), true);
    $bookingId = intval($data['booking_id'] ?? 0);
    $cashGiven = floatval($data['cash_given'] ?? 0);

    if ($bookingId <= 0 || $cashGiven <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid booking or cash amount.']);
        exit;
    }

    $row = fetchBooking($conn, $bookingId);
    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Booking not found.']);
        exit;
    }

    $totalAmount = floatval($row['totalAmount']);
    if ($cashGiven < $totalAmount) {
        echo json_encode(['success' => false, 'error' => 'Cash given is less than total amount.']);
        exit;
    }

    $balance = $cashGiven - $totalAmount;
    $time = date('Y-m-d H:i:s');

    if (savePayment($conn, $row, $cashGiven, $balance, $time)) {
        echo json_encode([
            'success' => true,
            'balance' => $balance,
            'booking_id' => intval($row['id'])
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $row = fetchBooking($conn, $id);
    if (!$row) {
        die("Booking not found.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $bookingId = intval($_POST['booking_id']);
    $cashGiven = floatval($_POST['cash_given']);

    $row = fetchBooking($conn, $bookingId);
    if (!$row) {
        die("Booking not found.");
    }

    $totalAmount = floatval($row['totalAmount']);
    if ($cashGiven < $totalAmount) {
        die("Cash given must be greater than or equal to the total amount.");
    }

    $balance = $cashGiven - $totalAmount;
    $time = date('Y-m-d H:i:s');

    if (savePayment($conn, $row, $cashGiven, $balance, $time)) {
        $paymentSaved = true;

        $recipientEmail = resolveLoggedInUserEmail($conn);
        if ($recipientEmail) {
            $ticketUrl = buildTicketAbsoluteUrl(intval($row['id']));
            if (sendTicketMail($recipientEmail, $row, $ticketUrl, $emailNote)) {
                $emailSent = true;
            }
        } else {
            $emailNote = 'No logged-in user email found to send the ticket confirmation.';
        }
    } else {
        die("Error saving payment: " . mysqli_error($conn));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Payment - FALCONS Theater</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
</head>
<body class="theme-body">
    <div class="page-shell">
        <section class="page-hero">
            <div class="hero-content">
                <span class="eyebrow">Cash Payment</span>
                <h1>Handle counter payment and return balance clearly.</h1>
                <p class="hero-copy">Use this screen to collect cash, save the payment, and move directly to the ticket once the booking is complete.</p>
            </div>
        </section>

        <section class="content-grid grid-two">
            <div class="glass-panel panel-padding stack">
                <div class="detail-list">
                    <div class="detail-item"><strong>Customer</strong><?= htmlspecialchars($row['name']) ?></div>
                    <div class="detail-item"><strong>Phone</strong><?= htmlspecialchars($row['phone']) ?></div>
                    <div class="detail-item"><strong>Movie</strong><?= htmlspecialchars($row['movie']) ?></div>
                    <div class="detail-item"><strong>Theater</strong><?= htmlspecialchars($row['theater'] ?? 'T1 - 4K') ?></div>
                    <div class="detail-item"><strong>Seats</strong><?= htmlspecialchars($row['seats']) ?></div>
                    <div class="detail-item"><strong>Total Amount</strong>Rs.<?= number_format($row['totalAmount'], 2) ?></div>
                </div>
            </div>

            <div class="glass-panel surface-panel form-card">
                <h2>Cash Counter</h2>
                <p class="form-copy">Enter the amount collected from the customer to calculate balance and finish the booking.</p>

                <?php if ($paymentSaved): ?>
                    <div class="status-banner status-success">Payment saved successfully.</div>
                    <?php if ($emailSent): ?>
                        <div class="status-banner status-success">Ticket confirmation has been sent to your email.</div>
                    <?php elseif ($emailNote !== ''): ?>
                        <div class="status-banner status-info"><?= htmlspecialchars($emailNote) ?></div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!$paymentSaved): ?>
                    <form method="POST" class="field-grid">
                        <input type="hidden" name="booking_id" value="<?= htmlspecialchars($row['id']) ?>">
                        <input class="input" type="number" step="0.01" min="<?= htmlspecialchars($row['totalAmount']) ?>" name="cash_given" placeholder="Cash Given (Rs.)" required>
                        <button class="btn" type="submit">Save Payment</button>
                    </form>
                <?php else: ?>
                    <div class="detail-list">
                        <div class="detail-item"><strong>Cash Given</strong>Rs.<?= number_format($cashGiven, 2) ?></div>
                        <div class="detail-item"><strong>Balance to Return</strong>Rs.<?= number_format($balance, 2) ?></div>
                    </div>
                    <div class="action-row">
                        <a href="print1.php?id=<?= intval($row['id']) ?>" class="btn">View Ticket</a>
                    </div>
                <?php endif; ?>

                <a href="index.php" class="btn-outline">Back to Home</a>
            </div>
        </section>
    </div>
</body>
</html>
