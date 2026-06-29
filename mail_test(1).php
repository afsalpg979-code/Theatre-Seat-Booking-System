<?php
require_once __DIR__ . '/mailer.php';

$message = '';
$messageType = 'info';
$targetEmail = '';
$configNote = '';
$configReady = theaterMailIsConfigured($configNote);
$config = theaterMailConfig();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetEmail = trim($_POST['test_email'] ?? '');

    if (!filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
        $message = 'Enter a valid email address for the test.';
        $messageType = 'error';
    } elseif (!$configReady) {
        $message = $configNote;
        $messageType = 'error';
    } else {
        try {
            $mail = createTheaterMailer();
            $mail->addAddress($targetEmail);
            $mail->isHTML(true);
            $mail->Subject = 'FALCONS Theater mail test';
            $mail->Body = '<h2>Mail test successful</h2><p>Your SMTP setup is working for FALCONS Theater.</p>';
            $mail->AltBody = 'Mail test successful. Your SMTP setup is working for FALCONS Theater.';
            $mail->send();

            $message = 'Test email sent successfully to ' . $targetEmail . '.';
            $messageType = 'success';
        } catch (\Throwable $e) {
            logTheaterMailFailure('mail_test', $e->getMessage());
            $message = theaterMailFailureMessage($e->getMessage());
            $messageType = 'error';
        }
    }
}

$maskedPassword = '';
if (!empty($config['password'])) {
    $maskedPassword = str_repeat('*', max(strlen((string) $config['password']) - 4, 0))
        . substr((string) $config['password'], -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Test - FALCONS Theater</title>
    <style>
        :root {
            --bg: #09131a;
            --panel: #11212c;
            --panel-soft: rgba(255, 255, 255, 0.05);
            --text: #eff7fb;
            --muted: #a9bec9;
            --border: rgba(255, 255, 255, 0.1);
            --accent: #f0c35a;
            --success: #7ff0bb;
            --error: #ff9e9e;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top, rgba(240, 195, 90, 0.2), transparent 28%),
                linear-gradient(135deg, #071018 0%, #0f1f2a 55%, #09131a 100%);
            padding: 24px;
        }

        .shell {
            max-width: 980px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        }

        h1, h2 {
            margin-top: 0;
        }

        p {
            color: var(--muted);
            line-height: 1.6;
        }

        .status {
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
            border: 1px solid transparent;
        }

        .status.info {
            background: rgba(240, 195, 90, 0.1);
            border-color: rgba(240, 195, 90, 0.25);
        }

        .status.success {
            background: rgba(127, 240, 187, 0.1);
            border-color: rgba(127, 240, 187, 0.28);
            color: var(--success);
        }

        .status.error {
            background: rgba(255, 158, 158, 0.1);
            border-color: rgba(255, 158, 158, 0.28);
            color: var(--error);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--panel-soft);
            color: var(--text);
            margin-bottom: 16px;
        }

        button {
            border: none;
            border-radius: 14px;
            padding: 14px 18px;
            background: linear-gradient(135deg, #f0c35a 0%, #ffd97a 100%);
            color: #2d2105;
            font-weight: 700;
            cursor: pointer;
        }

        .config-list {
            display: grid;
            gap: 12px;
        }

        .config-item {
            padding: 14px 16px;
            border-radius: 14px;
            background: var(--panel-soft);
            border: 1px solid var(--border);
        }

        .config-item strong {
            display: block;
            margin-bottom: 4px;
        }

        .hint {
            margin-top: 18px;
            font-size: 14px;
        }

        @media (max-width: 860px) {
            .shell {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="card">
            <h1>Mail Setup Test</h1>
            <p>Use this page to send a real test email through the same SMTP settings used for OTP and ticket delivery.</p>

            <?php if ($message !== ''): ?>
                <div class="status <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
            <?php elseif (!$configReady && $configNote !== ''): ?>
                <div class="status error"><?= htmlspecialchars($configNote) ?></div>
            <?php else: ?>
                <div class="status info">Current configuration looks complete. Send a test email to verify Gmail accepts the login.</div>
            <?php endif; ?>

            <form method="POST">
                <label for="test_email">Test recipient email</label>
                <input id="test_email" type="email" name="test_email" value="<?= htmlspecialchars($targetEmail) ?>" placeholder="you@example.com" required>
                <button type="submit">Send Test Email</button>
            </form>

            <p class="hint">If Gmail rejects the login, create a new 16-character Google App Password and update `MAIL_PASSWORD` or `mail_config.php`.</p>
        </section>

        <aside class="card">
            <h2>Current Mail Config</h2>
            <div class="config-list">
                <div class="config-item"><strong>Host</strong><?= htmlspecialchars((string) ($config['host'] ?? '')) ?></div>
                <div class="config-item"><strong>Port</strong><?= htmlspecialchars((string) ($config['port'] ?? '')) ?></div>
                <div class="config-item"><strong>Security</strong><?= htmlspecialchars((string) ($config['secure'] ?? '')) ?></div>
                <div class="config-item"><strong>Username</strong><?= htmlspecialchars((string) ($config['username'] ?? '')) ?></div>
                <div class="config-item"><strong>From Email</strong><?= htmlspecialchars((string) ($config['from_email'] ?? '')) ?></div>
                <div class="config-item"><strong>From Name</strong><?= htmlspecialchars((string) ($config['from_name'] ?? '')) ?></div>
                <div class="config-item"><strong>Password</strong><?= htmlspecialchars($maskedPassword) ?></div>
            </div>
        </aside>
    </div>
</body>
</html>
