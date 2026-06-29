<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

$adminName = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FALCONS MOVIES</title>
    <style>
        :root {
            --bg-dark: #07131d;
            --bg-mid: #10273a;
            --accent: #f3b61f;
            --accent-soft: #ffd975;
            --panel: rgba(255, 255, 255, 0.08);
            --panel-border: rgba(255, 255, 255, 0.14);
            --text-main: #f6fbff;
            --text-soft: #c2d6e5;
            --highlight: #15c39a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-main);
            background:
                radial-gradient(circle at top left, rgba(243, 182, 31, 0.20), transparent 28%),
                radial-gradient(circle at bottom right, rgba(21, 195, 154, 0.14), transparent 25%),
                linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-mid) 55%, #091621 100%);
            padding: 32px;
        }

        .shell {
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.4fr 0.9fr;
            gap: 22px;
            align-items: stretch;
            margin-bottom: 28px;
        }

        .hero-main,
        .hero-side {
            border: 1px solid var(--panel-border);
            border-radius: 28px;
            backdrop-filter: blur(16px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.26);
        }

        .hero-main {
            padding: 34px;
            background:
                linear-gradient(160deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04)),
                url("https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?auto=format&fit=crop&w=1600&q=80") center/cover;
            position: relative;
            overflow: hidden;
        }

        .hero-main::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(4, 11, 18, 0.55), rgba(4, 11, 18, 0.82));
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 620px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.16);
            color: var(--accent-soft);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0;
            font-size: clamp(2.1rem, 5vw, 4rem);
            line-height: 1.02;
        }

        .subtitle {
            margin: 18px 0 0;
            color: var(--text-soft);
            font-size: 1.05rem;
            line-height: 1.7;
            max-width: 560px;
        }

        .hero-side {
            background: linear-gradient(180deg, rgba(255,255,255,0.12), rgba(255,255,255,0.05));
            padding: 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 18px;
        }

        .admin-badge {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #ff8f1f);
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            box-shadow: 0 14px 30px rgba(243, 182, 31, 0.25);
        }

        .admin-card-title {
            font-size: 0.92rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-soft);
        }

        .admin-name {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .admin-note {
            color: var(--text-soft);
            line-height: 1.6;
        }

        .admin-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .ghost-link,
        .solid-link {
            text-decoration: none;
            border-radius: 14px;
            padding: 12px 16px;
            font-weight: 700;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .ghost-link:hover,
        .solid-link:hover {
            transform: translateY(-1px);
        }

        .solid-link {
            background: var(--highlight);
            color: #062119;
        }

        .ghost-link {
            background: rgba(255,255,255,0.08);
            color: var(--text-main);
            border: 1px solid rgba(255,255,255,0.12);
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 22px;
        }

        .card {
            position: relative;
            overflow: hidden;
            min-height: 220px;
            border-radius: 24px;
            padding: 26px;
            text-decoration: none;
            color: var(--text-main);
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.05));
            border: 1px solid var(--panel-border);
            box-shadow: 0 20px 45px rgba(0,0,0,0.22);
            transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .card:hover {
            transform: translateY(-6px);
            border-color: rgba(255, 217, 117, 0.45);
            box-shadow: 0 26px 55px rgba(0,0,0,0.30);
        }

        .card::before {
            content: "";
            position: absolute;
            inset: auto -40px -60px auto;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }

        .card-icon {
            width: 62px;
            height: 62px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 18px;
            color: #091018;
        }

        .card.featured .card-icon { background: linear-gradient(135deg, #ffd66b, #ff9f1f); }
        .card.bookings .card-icon { background: linear-gradient(135deg, #86efac, #34d399); }
        .card.messages .card-icon { background: linear-gradient(135deg, #93c5fd, #38bdf8); }
        .card.ratings .card-icon { background: linear-gradient(135deg, #f9a8d4, #fb7185); }
        .card.clients .card-icon { background: linear-gradient(135deg, #c4b5fd, #818cf8); }

        .card h2 {
            margin: 0 0 12px;
            font-size: 1.35rem;
        }

        .card p {
            margin: 0;
            line-height: 1.65;
            color: var(--text-soft);
            max-width: 240px;
        }

        @media (max-width: 900px) {
            .hero {
                grid-template-columns: 1fr;
            }

            body {
                padding: 20px;
            }

            .hero-main,
            .hero-side {
                border-radius: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="hero">
            <div class="hero-main">
                <div class="hero-content">
                    <span class="eyebrow">Control Center</span>
                    <h1>Run FALCONS Movies from one bold admin space.</h1>
                    <p class="subtitle">Manage upcoming releases, keep bookings under control, review customer messages, and stay on top of ratings without jumping between cluttered pages.</p>
                </div>
            </div>

            <div class="hero-side">
                <div class="admin-badge"><?= htmlspecialchars(strtoupper(substr($adminName, 0, 1))) ?></div>
                <div>
                    <div class="admin-card-title">Signed in as</div>
                    <div class="admin-name"><?= htmlspecialchars($adminName) ?></div>
                </div>
                <div class="admin-note">Use the admin tools below to update movies, review user activity, and keep the booking experience running smoothly.</div>
                <div class="admin-actions">
                    <a href="index.php" class="solid-link">Open Website</a>
                    <a href="admin_login.php?logout=1" class="ghost-link">Logout</a>
                </div>
            </div>
        </section>

        <section class="dashboard">
            <a href="upcoming.php" class="card featured">
                <div class="card-icon">NEW</div>
                <h2>Upcoming Movies</h2>
                <p>Add new releases, edit movie details, and keep the upcoming slate fresh and visible.</p>
            </a>

            <a href="admin_dashboard.php" class="card bookings">
                <div class="card-icon">BOOK</div>
                <h2>Bookings</h2>
                <p>Track customer bookings, review ticket details, and manage theatre reservations quickly.</p>
            </a>

            <a href="customer.php" class="card messages">
                <div class="card-icon">MAIL</div>
                <h2>Messages</h2>
                <p>Read customer contact messages and follow up on questions, complaints, or suggestions.</p>
            </a>

            <a href="rating.php" class="card ratings">
                <div class="card-icon">STAR</div>
                <h2>Ratings</h2>
                <p>Check public feedback, review comments, and monitor how audiences respond to your movies.</p>
            </a>

            <a href="ClientDetail.php" class="card clients">
                <div class="card-icon">USER</div>
                <h2>Client Details</h2>
                <p>Browse registered user details and keep an eye on customer records inside the system.</p>
            </a>
        </section>
    </div>
</body>
</html>
