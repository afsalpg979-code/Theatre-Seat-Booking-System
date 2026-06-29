<?php
include 'db.php';
require_once __DIR__ . '/mailer.php';

function fetchBookingCard(mysqli $conn, int $bookingId)
{
    $result = mysqli_query($conn, "SELECT * FROM bookings WHERE id = $bookingId");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }

    return null;
}

if (!isset($_GET['id'])) {
    echo "Invalid booking ID.";
    exit;
}

$id = intval($_GET['id']);
$row = fetchBookingCard($conn, $id);

if (!$row) {
    echo "No booking found for this ID.";
    exit;
}

$message = "";
$paymentSaved = false;
$emailSent = false;
$emailNote = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardName = trim($_POST['card_name'] ?? '');
    $cardNumber = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
    $expiry = trim($_POST['expiry'] ?? '');
    $cvv = preg_replace('/\D+/', '', $_POST['cvv'] ?? '');

    if ($cardName === '' || strlen($cardNumber) < 12 || strlen($cvv) < 3 || $expiry === '') {
        $message = "Enter valid card details.";
    } else {
        $name = mysqli_real_escape_string($conn, $row['name']);
        $phone = mysqli_real_escape_string($conn, $row['phone']);
        $movie = mysqli_real_escape_string($conn, $row['movie']);
        $theater = mysqli_real_escape_string($conn, $row['theater'] ?? 'T1 - 4K');
        $seats = mysqli_real_escape_string($conn, $row['seats']);
        $totalAmount = floatval($row['totalAmount']);
        $time = date('Y-m-d H:i:s');

        $sql = "INSERT INTO payments (name, phone, movie, theater, seats, totalAmount, cash_given, balance, time)
                VALUES ('$name', '$phone', '$movie', '$theater', '$seats', $totalAmount, $totalAmount, 0, '$time')";

        if (mysqli_query($conn, $sql)) {
            $paymentSaved = true;
            $message = "Card payment saved successfully.";
            
            // Send ticket confirmation email to customer
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
            $message = "Error saving payment: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Payment - FALCONS Theater</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
</head>
<body class="theme-body">
    <div class="page-shell">
        <section class="page-hero">
            <div class="hero-content">
                <span class="eyebrow">Card Payment</span>
                <h1>Complete your booking with card details.</h1>
                <p class="hero-copy">Review the reservation, enter card details, and continue to the ticket once payment is saved.</p>
            </div>
        </section>

        <section class="content-grid grid-two">
            <div class="glass-panel panel-padding stack">
                <div class="detail-list">
                    <div class="detail-item"><strong>Name</strong><?= htmlspecialchars($row['name']) ?></div>
                    <div class="detail-item"><strong>Phone</strong><?= htmlspecialchars($row['phone']) ?></div>
                    <div class="detail-item"><strong>Movie</strong><?= htmlspecialchars($row['movie']) ?></div>
                    <div class="detail-item"><strong>Theater</strong><?= htmlspecialchars($row['theater'] ?? 'T1 - 4K') ?></div>
                    <div class="detail-item"><strong>Seats</strong><?= htmlspecialchars($row['seats']) ?></div>
                    <div class="detail-item"><strong>Total Amount</strong>Rs.<?= number_format($row['totalAmount'], 2) ?></div>
                </div>
            </div>

            <div class="glass-panel surface-panel form-card">
                <h2>Enter Card Details</h2>
                <p class="form-copy">This demo flow stores the payment and then lets you open the booking ticket.</p>

                <?php if ($message !== ''): ?>
                    <div class="status-banner <?= $paymentSaved ? 'status-success' : 'status-error' ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if ($paymentSaved): ?>
                    <?php if ($emailSent): ?>
                        <div class="status-banner status-success">Ticket confirmation has been sent to your email.</div>
                    <?php elseif ($emailNote !== ''): ?>
                        <div class="status-banner status-error"><?= htmlspecialchars($emailNote) ?></div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!$paymentSaved): ?>
                    <form method="post" class="field-grid">
                        <input class="input" type="text" name="card_name" placeholder="Name on Card" required>
                        <input class="input" type="text" name="card_number" placeholder="Card Number" maxlength="19" required>
                        <div class="field-grid two-col">
                            <input class="input" type="text" name="expiry" placeholder="MM/YY" maxlength="5" required>
                            <input class="input" type="text" name="cvv" placeholder="CVV" maxlength="4" required>
                        </div>
                        <button class="btn" type="submit">Pay Now</button>
                    </form>
                <?php else: ?>
                    <div class="action-row">
                        <a href="print1.php?id=<?= $id ?>" class="btn">View Ticket</a>
                        <a href="buy.php?id=<?= $id ?>" class="btn-outline">Back to Payment Options</a>
                    </div>
                <?php endif; ?>

                <?php if (!$paymentSaved): ?>
                    <a href="buy.php?id=<?= $id ?>" class="btn-outline">Back to Payment Options</a>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
