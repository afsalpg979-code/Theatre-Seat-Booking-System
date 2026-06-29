<?php
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stars = intval($_POST["stars"] ?? 0);
    $comment = trim($_POST["comment"] ?? '');

    if ($stars < 1 || $stars > 5) {
        $message = 'Please choose a rating before submitting.';
        $messageType = 'error';
    } else {
        $conn = new mysqli("localhost", "root", "", "cinema_db");

        if ($conn->connect_error) {
            $message = 'Database connection failed.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO ratings (stars, comment) VALUES (?, ?)");
            $stmt->bind_param("is", $stars, $comment);

            if ($stmt->execute()) {
                $message = 'Thank you for your feedback!';
                $messageType = 'success';
            } else {
                $message = 'Error submitting rating.';
                $messageType = 'error';
            }

            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate FALCONS MOVIES</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --bg-dark: #07131d;
            --bg-mid: #10273a;
            --panel: rgba(255, 255, 255, 0.08);
            --panel-solid: #ffffff;
            --border: rgba(255, 255, 255, 0.14);
            --text: #f6fbff;
            --muted: #c2d6e5;
            --dark-text: #17202a;
            --gold: #f3b61f;
            --gold-soft: #ffd975;
            --success: #15c39a;
            --danger: #ff6b6b;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', Arial, sans-serif;
            background:
                linear-gradient(rgba(0, 0, 0, 0.68), rgba(0, 0, 0, 0.78)),
                url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1950&q=80') center/cover fixed;
            color: var(--text);
        }

        .page-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 42px 0;
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(340px, 480px);
            gap: 28px;
            align-items: stretch;
        }

        .intro-panel,
        .rating-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            background: rgba(7, 19, 29, 0.76);
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.36);
            backdrop-filter: blur(10px);
        }

        .intro-panel {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 560px;
            padding: 34px;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 12px;
            color: var(--gold);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            max-width: 680px;
            margin: 0;
            font-size: clamp(2.1rem, 5vw, 4.6rem);
            line-height: 1.02;
        }

        .intro-copy {
            max-width: 620px;
            margin: 20px 0 0;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
        }

        .quick-notes {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 34px;
        }

        .note {
            min-height: 96px;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.07);
        }

        .note strong {
            display: block;
            color: var(--gold-soft);
            font-size: 1.3rem;
        }

        .note span {
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: 0.84rem;
        }

        .rating-card {
            padding: 28px;
            background: rgba(255, 255, 255, 0.94);
            color: var(--dark-text);
        }

        .rating-card h2 {
            margin: 0;
            font-size: 1.35rem;
        }

        .form-copy {
            margin: 8px 0 22px;
            color: #4b6472;
            line-height: 1.6;
            font-size: 0.94rem;
        }

        .rating-panel {
            padding: 20px;
            border: 1px solid #e6ebf2;
            border-radius: 8px;
            background: #f8fafc;
        }

        .rating-label {
            display: block;
            margin-bottom: 10px;
            color: #334155;
            font-size: 0.84rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .rating-stars {
            direction: rtl;
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .rating-stars input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .rating-stars label {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: #cbd5e1;
            cursor: pointer;
            font-size: 2.35rem;
            line-height: 1;
            transition: transform 0.18s ease, color 0.18s ease, background 0.18s ease;
        }

        .rating-stars label:hover,
        .rating-stars label:hover ~ label,
        .rating-stars input:checked ~ label {
            color: var(--gold);
            background: rgba(243, 182, 31, 0.12);
            transform: translateY(-2px);
        }

        textarea {
            width: 100%;
            min-height: 132px;
            margin-top: 20px;
            padding: 14px 16px;
            border: 1px solid #d8e0ea;
            border-radius: 8px;
            background: #fff;
            color: var(--dark-text);
            font: inherit;
            resize: vertical;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        textarea::placeholder {
            color: #94a3b8;
        }

        textarea:focus {
            border-color: rgba(243, 182, 31, 0.75);
            box-shadow: 0 0 0 4px rgba(243, 182, 31, 0.14);
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 22px;
            flex-wrap: wrap;
        }

        .btn,
        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 12px 18px;
            border-radius: 8px;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .btn {
            border: 0;
            background: linear-gradient(135deg, var(--gold), var(--gold-soft));
            color: #07131d;
            box-shadow: 0 14px 30px rgba(243, 182, 31, 0.22);
        }

        .back-link {
            border: 1px solid #d8e0ea;
            background: #fff;
            color: #334155;
        }

        .btn:hover,
        .back-link:hover {
            transform: translateY(-1px);
        }

        .message {
            margin-top: 20px;
            padding: 14px 16px;
            border-radius: 8px;
            font-weight: 700;
        }

        .message.success {
            border: 1px solid rgba(21, 195, 154, 0.22);
            background: rgba(21, 195, 154, 0.12);
            color: #05765e;
        }

        .message.error {
            border: 1px solid rgba(255, 107, 107, 0.26);
            background: rgba(255, 107, 107, 0.12);
            color: #b42323;
        }

        @media (max-width: 880px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .intro-panel {
                min-height: auto;
            }
        }

        @media (max-width: 620px) {
            .page-shell {
                width: min(100% - 24px, 1180px);
                padding: 24px 0;
            }

            .intro-panel,
            .rating-card {
                padding: 22px;
            }

            .quick-notes {
                grid-template-columns: 1fr;
            }

            .rating-stars {
                gap: 4px;
            }

            .rating-stars label {
                width: 40px;
                height: 44px;
                font-size: 2rem;
            }

            .actions,
            .btn,
            .back-link {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<main class="page-shell">
    <section class="hero">
        <div class="intro-panel">
            <div>
                <span class="eyebrow">Audience Feedback</span>
                <h1>Rate your movie experience.</h1>
                <p class="intro-copy">Your stars and comments help FALCONS Theater understand what worked, what felt smooth, and what should improve for the next show.</p>
            </div>
            <div class="quick-notes">
                <div class="note">
                    <strong>1-5</strong>
                    <span>Choose a simple star rating.</span>
                </div>
                <div class="note">
                    <strong>Fast</strong>
                    <span>Submit feedback in a few seconds.</span>
                </div>
                <div class="note">
                    <strong>Useful</strong>
                    <span>Admins can review every response.</span>
                </div>
            </div>
        </div>

        <div class="rating-card">
            <h2>Share Your Rating</h2>
            <p class="form-copy">Select stars, add an optional note, and submit your feedback.</p>

            <form method="POST" action="">
                <div class="rating-panel">
                    <span class="rating-label">Your Rating</span>
                    <div class="rating-stars">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="stars" value="<?= $i ?>" required>
                            <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i === 1 ? '' : 's' ?>">&#9733;</label>
                        <?php endfor; ?>
                    </div>
                    <textarea name="comment" rows="4" placeholder="Write a comment (optional)..."></textarea>
                </div>

                <div class="actions">
                    <button class="btn" type="submit">Submit Rating</button>
                    <a href="index.php" class="back-link">Back to Home</a>
                </div>
            </form>

            <?php if ($message !== ''): ?>
                <div class="message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
