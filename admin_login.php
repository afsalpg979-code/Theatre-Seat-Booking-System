<?php
session_start();
include 'db.php';
require_once __DIR__ . '/admin_rate_limit.php';

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $login_error = "Invalid login credentials.";
    } else {
        [$allowed, $retryAfter] = admin_rate_limit_check($username);
        if (!$allowed) {
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            $login_error = "Too many login attempts. Please try again later.";
        } else {
            $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE LOWER(username) = LOWER(?) LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            $passwordMatches = false;
            $legacyPlaintext = false;

            if ($admin) {
                $storedPassword = (string) $admin['password'];
                $passwordInfo = password_get_info($storedPassword);
                $isPasswordHash = ($passwordInfo['algo'] ?? 0) !== 0;

                if ($isPasswordHash) {
                    $passwordMatches = password_verify($password, $storedPassword);
                } else {
                    // One-time compatibility migration for legacy plaintext records.
                    // A successful legacy login immediately replaces the plaintext value
                    // with a strong password_hash() value. Plaintext is never accepted again.
                    $passwordMatches = hash_equals($storedPassword, $password);
                    $legacyPlaintext = $passwordMatches;
                }
            }

            if ($admin && $passwordMatches) {
                if ($legacyPlaintext) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE admin SET password = ? WHERE id = ? LIMIT 1");
                    if (!$update) {
                        admin_rate_limit_failure($username);
                        $login_error = "Unable to complete login securely. Please try again later.";
                    } else {
                        $update->bind_param("si", $newHash, $admin['id']);
                        $updated = $update->execute();
                        $update->close();

                        if (!$updated) {
                            admin_rate_limit_failure($username);
                            $login_error = "Unable to complete login securely. Please try again later.";
                        } else {
                            $passwordMatches = true;
                        }
                    }
                }

                if ($passwordMatches && $login_error === '') {
                    admin_rate_limit_reset($username);
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $admin['username'];
                    header("Location: AdminOnly.php");
                    exit();
                }
            }

            if ($login_error === '') {
                admin_rate_limit_failure($username);
                $login_error = "Invalid login credentials.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - FALCONS Theater</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="theme.css">
</head>
<body class="theme-body">
  <div class="page-shell" style="max-width: 760px;">
    <section class="page-hero">
      <div class="hero-content">
        <span class="eyebrow">Admin Access</span>
        <h1>Secure the control room before you manage the theater.</h1>
        <p class="hero-copy">Use your admin credentials to access bookings, upcoming releases, messages, and customer feedback from one dashboard.</p>
      </div>
    </section>

    <section class="content-grid" style="margin-top: 24px;">
      <div class="glass-panel surface-panel form-card">
        <h2>Admin Login</h2>
        <p class="form-copy">Use your administrator credentials.</p>

        <?php if ($login_error): ?>
          <div class="status-banner status-error"><?= htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="admin_login.php" class="field-grid">
          <input class="input" type="text" name="username" placeholder="Username" autocomplete="username" required />
          <input class="input" type="password" name="password" placeholder="Password" autocomplete="current-password" required />
          <button class="btn" type="submit">Login</button>
        </form>

        <a href="index.php" class="btn-outline" style="margin-top: 14px;">Back to Home</a>
      </div>
    </section>
  </div>
</body>
</html>
