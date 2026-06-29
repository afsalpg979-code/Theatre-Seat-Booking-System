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

function formatTicketDateTime($value)
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    if ($value === '' || $value === '0000-00-00 00:00:00' || $value === '0000-00-00') {
        return null;
    }

    try {
        $date = new DateTime($value);
        return $date->format('d M Y, h:i A');
    } catch (Exception $exception) {
        return null;
    }
}

$row = mysqli_fetch_assoc($result);
$movie = isset($row['movie']) && $row['movie'] !== '' ? $row['movie'] : 'Featured Show';
$theater = isset($row['theater']) && $row['theater'] !== '' ? $row['theater'] : 'T1 - 4K';
$rawShowTime = $row['time'] ?? '';
$rawBookingDate = $row['created_at'] ?? '';
$showTime = formatTicketDateTime($rawShowTime);
$bookingDate = formatTicketDateTime($rawBookingDate);

if ($showTime === null && $bookingDate !== null) {
    $showTime = $bookingDate;
}

if ($showTime === null) {
    $showTime = 'Time not available';
}

if ($bookingDate === null) {
    $bookingDate = formatTicketDateTime(date('Y-m-d H:i:s')) ?: date('d M Y, h:i A');
}

$seatList = isset($row['seats']) && $row['seats'] !== '' ? $row['seats'] : 'Not Assigned';
$phone = isset($row['phone']) && $row['phone'] !== '' ? $row['phone'] : 'Not Available';
$guestName = isset($row['name']) && $row['name'] !== '' ? $row['name'] : 'Guest';
$amount = isset($row['totalAmount']) ? number_format((float) $row['totalAmount'], 2) : '0.00';
$seatCount = count(array_filter(array_map('trim', explode(',', $seatList))));
$bookingCode = 'FT' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Ticket - FALCONS Theater</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Special+Elite&display=swap" rel="stylesheet">
    <style>
        :root {
            --page-bg: #10181d;
            --ticket-bg: #f8eedc;
            --ticket-bg-2: #f4e7d1;
            --ink: #211d18;
            --muted: #7f7464;
            --line: #eadcc6;
            --pill-bg: #f3e4b6;
            --pill-ink: #bb8a16;
            --card-bg: rgba(255, 255, 255, 0.72);
            --card-shadow: rgba(90, 66, 18, 0.08);
            --btn-shadow: rgba(187, 138, 22, 0.22);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 28px 16px;
            background:
                radial-gradient(circle at top, rgba(255, 232, 180, 0.12), transparent 24%),
                linear-gradient(180deg, #162127 0%, var(--page-bg) 100%);
            color: var(--ink);
            font-family: "DM Sans", sans-serif;
        }

        .ticket-shell {
            width: min(100%, 760px);
            margin: 0 auto;
        }

        .ticket {
            background: linear-gradient(180deg, var(--ticket-bg) 0%, var(--ticket-bg-2) 100%);
            border-radius: 34px;
            padding: 28px;
            box-shadow: 0 26px 60px rgba(0, 0, 0, 0.28);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .brand {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 26px;
        }

        .brand-mark {
            font-family: "Special Elite", serif;
            font-size: clamp(2rem, 5vw, 3.25rem);
            line-height: 0.98;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin: 0;
        }

        .sub-copy {
            margin: 14px 0 0;
            max-width: 220px;
            color: #6d6354;
            font-family: "Special Elite", serif;
            font-size: 1.12rem;
            line-height: 1.35;
        }

        .status-badge,
        .booking-code {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: linear-gradient(180deg, #f8edc9 0%, #f1dfae 100%);
            color: var(--pill-ink);
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.55);
        }

        .status-badge {
            padding: 14px 24px;
            min-width: 164px;
        }

        .movie-title {
            margin: 18px 0 18px;
            font-family: "Special Elite", serif;
            font-size: clamp(2rem, 5.6vw, 3rem);
            line-height: 1.08;
        }

        .booking-code {
            padding: 12px 18px;
            margin-bottom: 24px;
            font-size: 1.08rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .info-card {
            background: var(--card-bg);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 22px 18px 20px;
            box-shadow: 0 8px 22px var(--card-shadow);
            min-height: 118px;
        }

        .label {
            display: block;
            margin-bottom: 14px;
            color: var(--muted);
            font-family: "Special Elite", serif;
            font-size: 0.95rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.32;
            word-break: break-word;
        }

        .meta-strip {
            margin-top: 22px;
            padding: 18px 20px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.46);
            border: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .meta-strip .label {
            margin-bottom: 8px;
            font-size: 0.82rem;
        }

        .meta-strip .value {
            font-size: 1.18rem;
        }

        .meta-note {
            margin: 20px 2px 0;
            color: #736958;
            font-size: 0.98rem;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 13px 22px;
            border-radius: 999px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.98rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(180deg, #f3de9e 0%, #e9c96f 100%);
            color: #553c03;
            box-shadow: 0 12px 22px var(--btn-shadow);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.55);
            border-color: var(--line);
            color: #4a4034;
        }

        @media (max-width: 640px) {
            body {
                padding: 18px 12px;
            }

            .ticket {
                padding: 24px 18px;
                border-radius: 28px;
            }

            .brand {
                flex-direction: column;
                align-items: flex-start;
            }

            .status-badge {
                min-width: 0;
                padding-inline: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-card {
                min-height: 0;
            }

            .value {
                font-size: 1.28rem;
            }

            .actions .btn {
                width: 100%;
            }
        }

        @media print {
            body {
                padding: 0;
                background: #ffffff;
            }

            .ticket-shell {
                width: 100%;
            }

            .ticket {
                box-shadow: none;
                border: none;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-shell">
        <div class="ticket">
            <div class="brand">
                <div>
                    <h1 class="brand-mark">FALCONS<br>THEATER</h1>
                    <p class="sub-copy">Official movie entry pass</p>
                </div>
                <div class="status-badge">Confirmed</div>
            </div>

            <h2 class="movie-title"><?= htmlspecialchars($movie) ?></h2>
            <div class="booking-code"><?= htmlspecialchars($bookingCode) ?></div>

            <div class="info-grid">
                <div class="info-card">
                    <span class="label">Guest Name</span>
                    <div class="value"><?= htmlspecialchars($guestName) ?></div>
                </div>
                <div class="info-card">
                    <span class="label">Contact</span>
                    <div class="value"><?= htmlspecialchars($phone) ?></div>
                </div>
                <div class="info-card">
                    <span class="label">Show Time</span>
                    <div class="value"><?= htmlspecialchars($showTime) ?></div>
                </div>
                <div class="info-card">
                    <span class="label">Theater</span>
                    <div class="value"><?= htmlspecialchars($theater) ?></div>
                </div>
                <div class="info-card">
                    <span class="label">Booking Date</span>
                    <div class="value"><?= htmlspecialchars($bookingDate) ?></div>
                </div>
                <div class="info-card">
                    <span class="label">Seats</span>
                    <div class="value"><?= htmlspecialchars($seatList) ?></div>
                </div>
                <div class="info-card">
                    <span class="label">Ticket Count</span>
                    <div class="value"><?= $seatCount > 0 ? $seatCount : 1 ?></div>
                </div>
            </div>

            <div class="meta-strip">
                <div>
                    <span class="label">Booking Status</span>
                    <div class="value">Booking secured successfully</div>
                </div>
                <div>
                    <span class="label">Total Paid</span>
                    <div class="value">Rs <?= htmlspecialchars($amount) ?></div>
                </div>
            </div>

            <p class="meta-note">Please carry this ticket at entry. Seat allotment and show access are valid only for the details printed here.</p>

            <div class="actions">
                <button class="btn btn-primary" onclick="window.print()">Print Ticket</button>
                <a href="index.php" class="btn btn-secondary">Back to Movies</a>
            </div>
        </div>
    </div>
</body>
</html>
