<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - FALCONS MOVIES</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
</head>
<body class="theme-body">
    <div class="page-shell">
        <section class="page-hero">
            <div class="hero-content">
                <span class="eyebrow">Contact</span>
                <h1>Send us your questions, feedback, or theatre suggestions.</h1>
                <p class="hero-copy">Whether you spotted an issue, want to suggest a movie, or just have feedback about the booking experience, we’re ready to hear from you.</p>
            </div>
        </section>

        <section class="content-grid grid-two">
            <div class="glass-panel surface-panel form-card">
                <h2>Write to FALCONS MOVIES</h2>
                <p class="form-copy">Drop a message and we’ll keep it in the system for admin review.</p>
                <form method="POST" action="message.php" class="field-grid">
                    <input class="input" type="text" name="name" placeholder="Your Name" required>
                    <input class="input" type="email" name="email" placeholder="Your Email" required>
                    <textarea class="textarea" name="message" rows="5" placeholder="Your Message..." required></textarea>
                    <button class="btn" type="submit">Send Message</button>
                </form>
            </div>

            <aside class="glass-panel panel-padding stack">
                <div>
                    <span class="eyebrow">Reach Out</span>
                    <p class="hero-copy">Use this page for support, feature ideas, booking concerns, or general feedback about FALCONS Theater.</p>
                </div>

                <div class="detail-list">
                    <div class="detail-item">
                        <strong>Fast contact flow</strong>
                        Your message is stored directly in the system for admin follow-up.
                    </div>
                    <div class="detail-item">
                        <strong>Best for</strong>
                        Support questions, feedback, corrections, and movie requests.
                    </div>
                    <div class="detail-item">
                        <strong>Need more context?</strong>
                        You can also review our About page to understand the site’s purpose and features.
                    </div>
                </div>

                <div class="action-row">
                    <a href="about.php" class="btn-outline">About Us</a>
                    <a href="index.php" class="btn-muted">Back to Home</a>
                </div>
            </aside>
        </section>
    </div>
</body>
</html>
