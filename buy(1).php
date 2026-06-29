<?php
include 'db.php';

if (!isset($_GET['id'])) {
    echo "Invalid booking ID.";
    exit;
}

$id = intval($_GET['id']);
$result = mysqli_query($conn, "SELECT * FROM bookings WHERE id = $id");

if (!$result || mysqli_num_rows($result) === 0) {
    echo "No booking found for this ID.";
    exit;
}

$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Options - FALCONS Theater</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
</head>
<body class="theme-body">
    <div class="page-shell">
        <section class="page-hero">
            <div class="hero-content">
                <span class="eyebrow">Payment Choice</span>
                <h1>Select how you want to complete this booking.</h1>
                <p class="hero-copy">Review the reservation details below, then continue with the payment method that fits your booking flow.</p>
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

            <div class="glass-panel panel-padding stack">
                <h2 class="section-title">Choose Payment Method</h2>
                <p class="intro-copy">Each option takes you into its own confirmation flow so you can complete the booking comfortably on desktop or mobile.</p>
                <div class="action-row">
                    <a href="QR.php?id=<?= $id ?>" class="btn">Pay with UPI</a>
                    <a href="card.php?id=<?= $id ?>" class="btn-outline">Pay with Card</a>
                    <a href="cash.php?id=<?= $id ?>" class="btn-muted">Pay with Cash</a>
                </div>
                <a href="index.php" class="btn-outline">Back to Movies</a>
            </div>
        </section>
    </div>
</body>
</html>
