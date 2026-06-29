<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = mysqli_query($conn, "SELECT * FROM bookings WHERE id = $id");

    if ($row = mysqli_fetch_assoc($result)) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Booking Receipt</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    padding: 30px;
                }
                .receipt {
                    background: #fff;
                    padding: 20px;
                    border-radius: 10px;
                    max-width: 500px;
                    margin: auto;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                h2 {
                    text-align: center;
                    color: #333;
                }
                p {
                    font-size: 16px;
                    margin: 10px 0;
                }
            </style>
        </head>
        <body>
            <div class="receipt">
                <h2>FALCONS Theater - Booking Receipt</h2>
                <p><strong>Name:</strong> <?= htmlspecialchars($row['name']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($row['phone']) ?></p>
                <p><strong>Movie:</strong> <?= htmlspecialchars($row['movie']) ?></p>
                <p><strong>Theater:</strong> <?= htmlspecialchars($row['theater'] ?? 'T1 - 4K') ?></p>
                <p><strong>Seats:</strong> <?= htmlspecialchars($row['seats']) ?></p>
                <p><strong>Total Amount:</strong> ₹<?= number_format($row['totalAmount'], 2) ?></p>
                <p><strong>Time:</strong> <?= htmlspecialchars($row['time']) ?></p>
            </div>
        </body>
        </html>
        <?php
    } else {
        echo "No booking found.";
    }
} else {
    echo "Invalid booking ID.";
}
?>
