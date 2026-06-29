<?php
include 'db.php';

if (!isset($_GET['id'])) {
    die("Invalid booking ID.");
}

$id = intval($_GET['id']);
$result = mysqli_query($conn, "SELECT * FROM bookings WHERE id = $id");

if (!$result || mysqli_num_rows($result) === 0) {
    die("Booking not found.");
}

$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cash Payment - FALCONS Theater</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            padding: 30px;
            color: #333;
        }
        .cash-box {
            max-width: 500px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0,0,0,0.15);
        }
        h2 {
            text-align: center;
            color: #FFD700;
        }
        p {
            font-size: 18px;
            margin: 10px 0;
        }
        input[type="number"] {
            padding: 10px;
            width: 100%;
            margin: 10px 0;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            background: #FFD700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background: #e6c200;
        }
        .back-btn {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            background: #555;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
        }
        .result {
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="cash-box">
        <h2>Cash Payment - FALCONS Theater</h2>

        <p><strong>Customer Name:</strong> <?= htmlspecialchars($row['name']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($row['phone']) ?></p>
        <p><strong>Movie Name:</strong> <?= htmlspecialchars($row['movie']) ?></p>
        <p><strong>Seats:</strong> <?= htmlspecialchars($row['seats']) ?></p>
        <p><strong>Total Amount:</strong> ₹<?= number_format($row['totalAmount'], 2) ?></p>

        <label>Enter Cash Given by Customer (₹):</label>
        <input type="number" id="cashGiven" step="0.01" required>
        <button onclick="processPayment()">Calculate Balance & Save</button>

        <div id="result" class="result"></div>

        <a href="index.php" class="back-btn">← Back</a>
    </div>

    <script>
        function processPayment() {
            const cashGiven = parseFloat(document.getElementById('cashGiven').value);
            if (isNaN(cashGiven) || cashGiven <= 0) {
                alert('Please enter a valid cash amount.');
                return;
            }

            fetch('cash.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    booking_id: <?= $id ?>,
                    cash_given: cashGiven
                })
            })
            .then(res => res.json())
            .then(data => {
                const result = document.getElementById('result');
                if (data.success) {
                    result.innerHTML = `<span class="success">✅ Payment saved! Balance to return: ₹${data.balance.toFixed(2)}</span>`;
                } else {
                    result.innerHTML = `<span class="error">❌ ${data.error}</span>`;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Something went wrong.');
            });
        }
    </script>
</body>
</html>
