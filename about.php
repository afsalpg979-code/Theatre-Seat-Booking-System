<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - FALCONS MOVIES</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
    <style>
        .developer-section {
            margin-top: 32px;
        }

        .developer-card {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 28px;
            align-items: center;
        }

        .developer-photo {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            border-radius: 24px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }

        .developer-title {
            margin: 0 0 10px;
            font-size: 2rem;
        }

        .developer-role {
            display: inline-block;
            margin-bottom: 16px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 215, 0, 0.12);
            color: #ffd86b;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        .developer-copy {
            margin: 0 0 14px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.84);
        }

        @media (max-width: 720px) {
            .developer-card {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .developer-photo {
                max-width: 240px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body class="theme-body">
    <div class="page-shell">
        <section class="page-hero">
            <div class="hero-content">
                <span class="eyebrow">About FALCONS</span>
                <h1>Movie discovery, booking, and cinema excitement in one place.</h1>
                <p class="hero-copy">FALCONS MOVIES brings together multilingual releases, seat booking, customer feedback, and a theatre-first experience in a single platform designed for both movie lovers and admins.</p>
            </div>
        </section>

        <section class="content-grid grid-two">
            <div class="glass-panel surface-panel panel-padding stack">
                <div>
                    <h2 class="section-title">What We Do</h2>
                    <p class="intro-copy">We help audiences explore current and upcoming movies across English, Malayalam, Hindi, and Tamil. The goal is to make booking feel clear, quick, and enjoyable from the first click to the ticket printout.</p>
                </div>

                <div class="mini-grid">
                    <div class="feature-card">
                        <div class="inner">
                            <div class="feature-badge">MOV</div>
                            <h3>Movie Discovery</h3>
                            <p class="movie-meta">Browse visually rich movie cards, language filters, descriptions, and release information without clutter.</p>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="inner">
                            <div class="feature-badge">SEA</div>
                            <h3>Seat Booking</h3>
                            <p class="movie-meta">Customers can select seats, confirm booking details, and continue to payment with a simple theatre-style flow.</p>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="inner">
                            <div class="feature-badge">PAY</div>
                            <h3>Payment Options</h3>
                            <p class="movie-meta">Cash, card, and UPI flows are built into the booking experience with ticket and receipt support.</p>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="inner">
                            <div class="feature-badge">ADM</div>
                            <h3>Admin Control</h3>
                            <p class="movie-meta">Admins can manage upcoming movies, bookings, ratings, customer messages, and client details from one dashboard.</p>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="glass-panel panel-padding stack">
                <div>
                    <span class="eyebrow">Our Mission</span>
                    <p class="hero-copy">We want theatre browsing to feel modern and direct, whether someone is looking for tonight’s show or planning for an upcoming release.</p>
                </div>

                <div class="detail-list">
                    <div class="detail-item">
                        <strong>Audience first</strong>
                        Smooth browsing, clean booking, and easy access across devices.
                    </div>
                    <div class="detail-item">
                        <strong>Multilingual focus</strong>
                        Regional and international releases share the same stage.
                    </div>
                    <div class="detail-item">
                        <strong>Built for continuity</strong>
                        Admin tools and user tools are connected instead of scattered.
                    </div>
                </div>

                <div class="action-row">
                    <a href="contact.php" class="btn">Contact Us</a>
                    <a href="index.php" class="btn-outline">Back to Home</a>
                </div>
            </aside>
        </section>

        <section class="glass-panel panel-padding developer-section">
            <div class="developer-card">
                <img src="AFSALPG.jpg" alt="AFSAL PG" class="developer-photo">
                <div>
                    <span class="eyebrow">Developed By</span>
                    <h2 class="developer-title">AFSAL PG</h2>
                    <div class="developer-role">Developer</div>
                    <p class="developer-copy">This theatre booking system was developed by AFSAL PG with a focus on simple booking flow, clean admin control, and a better movie experience for users.</p>
                    <p class="developer-copy">From movie browsing and seat selection to ticket printing and feedback collection, the platform was designed to feel practical, modern, and easy to use.</p>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
