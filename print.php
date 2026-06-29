<?php
$name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Guest';
$phone = isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : 'Not Available';
$seats = isset($_GET['seats']) ? htmlspecialchars($_GET['seats']) : 'Not Assigned';
$movie = isset($_GET['movie']) ? htmlspecialchars($_GET['movie']) : 'Featured Show';
$totalAmount = isset($_GET['totalAmount']) ? number_format((float) $_GET['totalAmount'], 2) : '0.00';
$time = isset($_GET['time']) ? htmlspecialchars($_GET['time']) : 'Not Assigned';
$bookingDate = date('Y-m-d H:i:s');
$seatCount = count(array_filter(array_map('trim', explode(',', $seats))));
$bookingCode = 'FT' . strtoupper(substr(md5($name . $phone . $seats . $time), 0, 6));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Ticket - FALCONS Theater</title>
    <style>
        :root {
            --bg-1: #07111f;
            --bg-2: #15253e;
            --card: #f7f1e6;
            --ink: #1d1d1d;
            --muted: #6e6253;
            --accent: #c69214;
            --accent-dark: #8f6408;
            --danger: #bf3b3b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top, rgba(198, 146, 20, 0.18), transparent 28%),
                linear-gradient(135deg, var(--bg-1), var(--bg-2));
            color: var(--ink);
        }

        .ticket-shell {
            width: min(100%, 860px);
        }

        .ticket {
            display: grid;
            grid-template-columns: 1.7fr 0.85fr;
            background: var(--card);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.35);
            position: relative;
        }

        .ticket::before,
        .ticket::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #0e1a2c;
            transform: translateY(-50%);
        }

        .ticket::before {
            left: -14px;
        }

        .ticket::after {
            right: -14px;
        }

        .main-panel {
            padding: 34px 34px 28px;
        }

        .stub-panel {
            background: linear-gradient(180deg, #23180b, #15110b);
            color: #f9ecd0;
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-left: 2px dashed rgba(198, 146, 20, 0.5);
        }

        .brand {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 28px;
        }

        .brand h1 {
            margin: 0;
            font-size: 2rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .meta-note,
        .stub-note {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .status-badge {
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(198, 146, 20, 0.12);
            color: var(--accent-dark);
            font-weight: bold;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .movie-title {
            font-size: 2.2rem;
            margin: 0 0 10px;
            line-height: 1.1;
        }

        .booking-code {
            display: inline-block;
            margin-bottom: 24px;
            padding: 8px 12px;
            border-radius: 12px;
            background: rgba(29, 29, 29, 0.06);
            color: var(--accent-dark);
            font-weight: bold;
            letter-spacing: 0.08em;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-card {
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.55);
            border: 1px solid rgba(29, 29, 29, 0.08);
        }

        .label {
            display: block;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
            font-weight: bold;
        }

        .value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink);
            word-break: break-word;
        }

        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, rgba(198, 146, 20, 0.12), rgba(198, 146, 20, 0.22));
            border: 1px solid rgba(198, 146, 20, 0.3);
            border-radius: 20px;
            padding: 18px 20px;
            margin-bottom: 18px;
        }

        .total-box strong {
            font-size: 1.6rem;
            color: var(--danger);
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 26px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 13px 22px;
            border-radius: 999px;
            border: none;
            text-decoration: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.96rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #f1c75b);
            color: #18120a;
            box-shadow: 0 14px 28px rgba(198, 146, 20, 0.25);
        }

        .btn-secondary {
            background: rgba(29, 29, 29, 0.08);
            color: var(--ink);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .stub-title {
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .stub-code {
            margin: 12px 0 18px;
            font-size: 1.2rem;
            font-weight: bold;
            letter-spacing: 0.12em;
            color: #ffd978;
        }

        .stub-row {
            margin-bottom: 16px;
        }

        .stub-row .label {
            color: rgba(249, 236, 208, 0.65);
        }

        .stub-row .value {
            color: #fff4d6;
        }

        .barcode {
            margin-top: 24px;
            height: 64px;
            border-radius: 12px;
            background:
                repeating-linear-gradient(
                    90deg,
                    #fff4d6 0 4px,
                    transparent 4px 8px,
                    #fff4d6 8px 10px,
                    transparent 10px 14px
                );
            opacity: 0.9;
        }

        .stub-note {
            color: rgba(249, 236, 208, 0.7);
            font-size: 0.88rem;
            line-height: 1.5;
        }

        @media (max-width: 760px) {
            .ticket {
                grid-template-columns: 1fr;
            }

            .stub-panel {
                border-left: none;
                border-top: 2px dashed rgba(198, 146, 20, 0.5);
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .movie-title {
                font-size: 1.8rem;
            }
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .ticket-shell {
                width: 100%;
            }

            .ticket {
                box-shadow: none;
                border: 1px solid #ddd;
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
            <section class="main-panel">
                <div class="brand">
                    <div>
                        <h1>FALCONS Theater</h1>
                        <p class="meta-note">Official movie entry pass</p>
                    </div>
                    <div class="status-badge">Confirmed</div>
                </div>

                <h2 class="movie-title"><?= $movie ?></h2>
                <div class="booking-code"><?= $bookingCode ?></div>

                <div class="info-grid">
                    <div class="info-card">
                        <span class="label">Guest Name</span>
                        <span class="value"><?= $name ?></span>
                    </div>
                    <div class="info-card">
                        <span class="label">Contact</span>
                        <span class="value"><?= $phone ?></span>
                    </div>
                    <div class="info-card">
                        <span class="label">Show Time</span>
                        <span class="value"><?= $time ?></span>
                    </div>
                    <div class="info-card">
                        <span class="label">Booking Date</span>
                        <span class="value"><?= $bookingDate ?></span>
                    </div>
                    <div class="info-card">
                        <span class="label">Seats</span>
                        <span class="value"><?= $seats ?></span>
                    </div>
                    <div class="info-card">
                        <span class="label">Ticket Count</span>
                        <span class="value"><?= $seatCount > 0 ? $seatCount : 1 ?></span>
                    </div>
                </div>

                <div class="total-box">
                    <div>
                        <span class="label">Total Paid</span>
                        <div class="value">Booking secured successfully</div>
                    </div>
                    <strong>Rs <?= $totalAmount ?></strong>
                </div>

                <p class="meta-note">Please carry this ticket at entry. Seat allotment and show access are valid only for the details printed here.</p>

                <div class="actions">
                    <button class="btn btn-primary" onclick="window.print()">Print Ticket</button>
                    <a href="index.php" class="btn btn-secondary">Back to Movies</a>
                </div>
            </section>

            <aside class="stub-panel">
                <div>
                    <h3 class="stub-title">Ticket Stub</h3>
                    <div class="stub-code"><?= $bookingCode ?></div>

                    <div class="stub-row">
                        <span class="label">Movie</span>
                        <div class="value"><?= $movie ?></div>
                    </div>
                    <div class="stub-row">
                        <span class="label">Show</span>
                        <div class="value"><?= $time ?></div>
                    </div>
                    <div class="stub-row">
                        <span class="label">Seats</span>
                        <div class="value"><?= $seats ?></div>
                    </div>
                    <div class="stub-row">
                        <span class="label">Amount</span>
                        <div class="value">Rs <?= $totalAmount ?></div>
                    </div>
                </div>

                <div>
                    <div class="barcode"></div>
                    <p class="stub-note">Present this printed ticket or a clear screenshot at the gate for a smooth entry.</p>
                </div>
            </aside>
        </div>
    </div>
</body>
</html>
