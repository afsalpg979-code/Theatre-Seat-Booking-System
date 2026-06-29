<?php
// Include the database connection
include 'db.php';
require_once __DIR__ . '/utils.php';

// Start the session
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// ✅ Handle delete request for bookings with prepared statement
$csrfToken = generateCSRFToken();

if (isset($_GET['delete_id']) && verifyCSRFToken($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
    $delete_id = intval($_GET['delete_id']);
    dbDelete($conn, 'bookings', $delete_id);
    header("Location: admin_dashboard.php");
    exit();
}

// ✅ Fetch bookings with prepared statement
$stmt = $conn->prepare("SELECT * FROM bookings ORDER BY time DESC");
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

function formatBookingTime($timeValue) {
    $timeValue = trim((string) $timeValue);

    if ($timeValue === '') {
        return 'Not Available';
    }

    $timestamp = strtotime($timeValue);
    if ($timestamp !== false) {
        return date('d M Y, h:i A', $timestamp);
    }

    return htmlspecialchars($timeValue);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bookings</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --bg-dark: #07131d;
            --bg-mid: #10273a;
            --panel: #ffffff;
            --panel-soft: #f6f8fb;
            --text: #17202a;
            --muted: #64748b;
            --line: #e6ebf2;
            --brand: #0f6ec7;
            --brand-dark: #084e91;
            --danger: #dc2626;
            --danger-soft: #fee2e2;
            --gold: #f3b61f;
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(243, 182, 31, 0.22), transparent 28rem),
                linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-mid) 58%, #091621 100%);
            margin: 0;
            padding: 32px 18px;
            min-height: 100vh;
            color: var(--text);
        }

        .dashboard-shell {
            max-width: 1180px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 22px;
            color: #fff;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 8px;
            color: var(--gold);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: clamp(1.7rem, 4vw, 2.7rem);
            line-height: 1.1;
        }

        .header-copy {
            margin: 10px 0 0;
            max-width: 620px;
            color: #c2d6e5;
            font-size: 0.96rem;
        }

        .summary-pill {
            min-width: 150px;
            padding: 16px 18px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.08);
            text-align: center;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.18);
        }

        .summary-pill strong {
            display: block;
            font-size: 2rem;
            line-height: 1;
        }

        .summary-pill span {
            display: block;
            margin-top: 6px;
            color: #c2d6e5;
            font-size: 0.82rem;
            font-weight: 500;
        }

        .table-card {
            overflow: hidden;
            border-radius: 8px;
            background: var(--panel);
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
        }

        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--line);
            background: var(--panel-soft);
        }

        .table-toolbar h2 {
            margin: 0;
            font-size: 1rem;
        }

        .table-toolbar p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 0.86rem;
            text-align: left;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .booking-table {
            width: 100%;
            min-width: 860px;
            margin: 0;
            border-collapse: collapse;
        }

        .booking-table th, .booking-table td {
            padding: 15px 16px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: middle;
        }

        .booking-table th {
            background: #0f2436;
            color: #eef7ff;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .booking-table td {
            color: #253244;
            font-size: 0.92rem;
        }

        .booking-table td:first-child {
            width: 70px;
            color: var(--muted);
            font-weight: 600;
        }

        .booking-table td:nth-child(4) {
            max-width: 180px;
            color: var(--brand-dark);
            font-weight: 600;
        }

        .booking-table td:nth-child(6) {
            font-weight: 700;
            white-space: nowrap;
        }

        .booking-table tr:nth-child(even) {
            background: #fafcff;
        }

        .booking-table tr:hover {
            background: #eef6ff;
        }

        .delete-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 8px 13px;
            border: 1px solid #fecaca;
            border-radius: 6px;
            background: var(--danger-soft);
            color: var(--danger);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 700;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }

        .delete-link:hover {
            background: var(--danger);
            border-color: var(--danger);
            color: #fff;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            margin: 22px 0 0;
            text-align: center;
            padding: 11px 18px;
            background: var(--brand);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 700;
            transition: background 0.2s, transform 0.2s;
        }

        .back-link:hover {
            background: var(--brand-dark);
            transform: translateY(-1px);
        }

        .empty-state {
            padding: 46px 20px;
            text-align: center;
            color: var(--muted);
            background: var(--panel);
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
        }

        @media (max-width: 720px) {
            body {
                padding: 22px 12px;
            }

            .dashboard-header,
            .table-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .summary-pill {
                width: 100%;
                text-align: left;
            }
        }
    </style>
</head>
<body>

<main class="dashboard-shell">
    <header class="dashboard-header">
        <div>
            <span class="eyebrow">Admin Panel</span>
            <h1>Bookings Dashboard</h1>
            <p class="header-copy">Review customer reservations, seat selections, payment totals, and remove incorrect bookings when needed.</p>
        </div>
        <div class="summary-pill">
            <strong><?= mysqli_num_rows($result) ?></strong>
            <span>Total Bookings</span>
        </div>
    </header>

<?php
if (mysqli_num_rows($result) > 0) {
    echo "<section class='table-card'>
            <div class='table-toolbar'>
                <div>
                    <h2>Recent Reservations</h2>
                    <p>Sorted by booking time, newest first.</p>
                </div>
                <a href='AdminOnly.php' class='back-link'>Back to Admin Home</a>
            </div>
            <div class='table-wrap'>
            <table class='booking-table'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Seats</th>
                    <th>Movie</th>
                    <th>Theater</th>
                    <th>Total Amount</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
                <td>". $row['id'] ."</td>
                <td>". htmlspecialchars($row['name']) ."</td>
                <td>". htmlspecialchars($row['phone']) ."</td>
                <td>". htmlspecialchars($row['seats']) ."</td>
                <td>". htmlspecialchars($row['movie']) ."</td>
                <td>". htmlspecialchars($row['theater'] ?? 'T1 - 4K') ."</td>
                <td>". htmlspecialchars($row['totalAmount']) ."</td>
                <td>". formatBookingTime($row['time']) ."</td>
                <td>
                    <a href='admin_dashboard.php?delete_id=". $row['id'] ."&csrf_token=". urlencode($csrfToken) ."' class='delete-link' onclick=\"return confirm('Are you sure you want to delete this booking?');\">Delete</a>
                </td>
              </tr>";
    }
    echo "</tbody></table></div></section>";
} else {
    echo "<div class='empty-state'>No bookings found.</div>";
}
?>

<?php if (mysqli_num_rows($result) === 0): ?>
    <a href="AdminOnly.php" class="back-link">Back to Admin Home</a>
<?php endif; ?>

</main>
</body>
</html>
