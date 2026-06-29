<?php
session_start();
require_once __DIR__ . '/app_config.php';
require_once __DIR__ . '/mailer.php';

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "cinema_db";
$googleClientId = AppConfig::GOOGLE_CLIENT_ID;

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "info";

function setFlashMessage(&$message, &$messageType, $text, $type = "info")
{
    $message = $text;
    $messageType = $type;
}

function buildUsernameFromEmail($email)
{
    $localPart = explode("@", $email)[0];
    $clean = preg_replace("/[^a-zA-Z0-9_]/", "", $localPart);
    return $clean !== "" ? $clean : "movie_user";
}

function sendOtpEmail($email, $otp, &$deliveryNote)
{
    return sendOtpMail($email, $otp, $deliveryNote);
}

function canUseOtpFallback()
{
    return class_exists('AppConfig') && AppConfig::ENABLE_OTP_FALLBACK;
}

function normalizeOtpCode($otp)
{
    return preg_replace('/\D+/', '', (string) $otp);
}

function setOtpDeliveryState($mode, $note = '')
{
    if (!isset($_SESSION["otp_flow"]) || !is_array($_SESSION["otp_flow"])) {
        return;
    }

    $_SESSION["otp_flow"]["delivery_mode"] = $mode;
    $_SESSION["otp_flow"]["delivery_note"] = $note;
}

function deliverOtpWithFallback($email, $otp, &$deliveryNote)
{
    if (sendOtpEmail($email, $otp, $deliveryNote)) {
        setOtpDeliveryState("email", $deliveryNote);
        return true;
    }

    if (!canUseOtpFallback()) {
        setOtpDeliveryState("failed", $deliveryNote);
        return false;
    }

    if (class_exists('Logger')) {
        Logger::warning('OTP fallback used because email delivery failed', [
            'email' => $email,
            'otp' => $otp,
            'mail_error' => $deliveryNote,
        ]);
    }

    $deliveryNote = "Email delivery failed, so development OTP fallback is active. Use this OTP: {$otp}";
    setOtpDeliveryState("fallback", $deliveryNote);
    return true;
}

function startOtpFlow($context, $payload)
{
    $_SESSION["otp_flow"] = [
        "context" => $context,
        "otp" => (string) random_int(100000, 999999),
        "expires_at" => time() + 600,
        "payload" => $payload,
        "delivery_mode" => "pending",
        "delivery_note" => "",
    ];
}

function clearOtpFlow()
{
    unset($_SESSION["otp_flow"]);
}

function clearPasswordResetFlow()
{
    unset($_SESSION["password_reset"]);
}

function fetchGoogleProfileFromToken($idToken)
{
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idToken);
    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "timeout" => 10,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return [false, "Could not verify Google token. Check internet access on the server."];
    }

    $data = json_decode($response, true);
    if (!is_array($data) || isset($data["error_description"])) {
        return [false, "Google sign-in verification failed."];
    }

    return [true, $data];
}

function loginUser($id, $username)
{
    $_SESSION["user_id"] = $id;
    $_SESSION["username"] = $username;
}

if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["login"])) {
        $email = trim($_POST["login_email"] ?? "");
        $password = $_POST["login_password"] ?? "";

        $stmt = $conn->prepare("SELECT id, username, password FROM clients WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $username, $hashedPassword);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                loginUser($id, $username);
                clearOtpFlow();
                header("Location: index.php");
                exit;
            }

            setFlashMessage($message, $messageType, "Invalid password.", "error");
        } else {
            setFlashMessage($message, $messageType, "No account found with that email.", "error");
        }

        $stmt->close();
    }

    if (isset($_POST["request_login_otp"])) {
        $email = trim($_POST["otp_login_email"] ?? "");

        $stmt = $conn->prepare("SELECT id, username FROM clients WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $username);
            $stmt->fetch();

            startOtpFlow("login", [
                "id" => $id,
                "username" => $username,
                "email" => $email,
            ]);

            $deliveryNote = "";
            $otp = $_SESSION["otp_flow"]["otp"];
            if (deliverOtpWithFallback($email, $otp, $deliveryNote)) {
                setFlashMessage($message, $messageType, $deliveryNote . " Enter the code below to log in.", "success");
            } else {
                clearOtpFlow();
                setFlashMessage($message, $messageType, $deliveryNote, "error");
            }
        } else {
            setFlashMessage($message, $messageType, "Create an account first, then use OTP login.", "error");
        }

        $stmt->close();
    }

    if (isset($_POST["request_password_reset"])) {
        $email = trim($_POST["reset_email"] ?? "");

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage($message, $messageType, "Enter a valid account email address.", "error");
        } else {
            $stmt = $conn->prepare("SELECT id, username FROM clients WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $username);
                $stmt->fetch();

                clearPasswordResetFlow();
                startOtpFlow("password_reset", [
                    "id" => $id,
                    "username" => $username,
                    "email" => $email,
                ]);

                $deliveryNote = "";
                $otp = $_SESSION["otp_flow"]["otp"];
                if (deliverOtpWithFallback($email, $otp, $deliveryNote)) {
                    setFlashMessage($message, $messageType, $deliveryNote . " Enter the code below to reset your password.", "success");
                } else {
                    clearOtpFlow();
                    setFlashMessage($message, $messageType, $deliveryNote, "error");
                }
            } else {
                clearPasswordResetFlow();
                setFlashMessage($message, $messageType, "No account found with that email.", "error");
            }

            $stmt->close();
        }
    }

    if (isset($_POST["signup"])) {
        $username = trim($_POST["signup_username"] ?? "");
        $email = trim($_POST["signup_email"] ?? "");
        $plainPassword = $_POST["signup_password"] ?? "";

        if ($username === "" || $email === "" || $plainPassword === "") {
            setFlashMessage($message, $messageType, "All sign-up fields are required.", "error");
        } else {
            $check = $conn->prepare("SELECT id FROM clients WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                setFlashMessage($message, $messageType, "Email already registered.", "error");
            } else {
                startOtpFlow("signup", [
                    "username" => $username,
                    "email" => $email,
                    "password" => password_hash($plainPassword, PASSWORD_DEFAULT),
                ]);

                $deliveryNote = "";
                $otp = $_SESSION["otp_flow"]["otp"];
                if (deliverOtpWithFallback($email, $otp, $deliveryNote)) {
                    setFlashMessage($message, $messageType, $deliveryNote . " Verify the code to finish sign up.", "success");
                } else {
                    clearOtpFlow();
                    setFlashMessage($message, $messageType, $deliveryNote, "error");
                }
            }

            $check->close();
        }
    }

    if (isset($_POST["verify_otp"])) {
        $enteredOtp = normalizeOtpCode($_POST["otp_code"] ?? "");
        $flow = $_SESSION["otp_flow"] ?? null;

        if (!$flow) {
            setFlashMessage($message, $messageType, "No OTP request found. Please request a new code.", "error");
        } elseif (time() > ($flow["expires_at"] ?? 0)) {
            clearOtpFlow();
            setFlashMessage($message, $messageType, "OTP expired. Please request a new code.", "error");
        } elseif (strlen($enteredOtp) !== AppConfig::OTP_LENGTH) {
            setFlashMessage($message, $messageType, "Enter a valid 6-digit OTP.", "error");
        } elseif (!hash_equals(normalizeOtpCode($flow["otp"] ?? ""), $enteredOtp)) {
            $activeOtpHint = ($flow["delivery_mode"] ?? "") === "fallback"
                ? " Email delivery is failing, so use the OTP shown on this page."
                : "";
            setFlashMessage($message, $messageType, "Invalid OTP. Please try again." . $activeOtpHint, "error");
        } elseif ($flow["context"] === "login") {
            loginUser($flow["payload"]["id"], $flow["payload"]["username"]);
            clearOtpFlow();
            header("Location: index.php");
            exit;
        } elseif ($flow["context"] === "password_reset") {
            $_SESSION["password_reset"] = [
                "id" => (int) $flow["payload"]["id"],
                "email" => $flow["payload"]["email"],
                "username" => $flow["payload"]["username"],
                "expires_at" => time() + 600,
            ];
            clearOtpFlow();
            setFlashMessage($message, $messageType, "OTP verified. Enter your new password below.", "success");
        } elseif ($flow["context"] === "signup") {
            $payload = $flow["payload"];
            $stmt = $conn->prepare("INSERT INTO clients (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $payload["username"], $payload["email"], $payload["password"]);

            if ($stmt->execute()) {
                $newUserId = $stmt->insert_id;
                loginUser($newUserId, $payload["username"]);
                clearOtpFlow();
                $stmt->close();
                header("Location: index.php");
                exit;
            }

            setFlashMessage($message, $messageType, "Could not create account: " . $stmt->error, "error");
            $stmt->close();
        }
    }

    if (isset($_POST["resend_otp"])) {
        $flow = $_SESSION["otp_flow"] ?? null;

        if (!$flow || empty($flow["payload"]["email"])) {
            setFlashMessage($message, $messageType, "No OTP session found. Start the login or sign-up flow again.", "error");
        } else {
            $_SESSION["otp_flow"]["otp"] = (string) random_int(100000, 999999);
            $_SESSION["otp_flow"]["expires_at"] = time() + 600;

            $deliveryNote = "";
            $email = $_SESSION["otp_flow"]["payload"]["email"];
            $otp = $_SESSION["otp_flow"]["otp"];

            if (deliverOtpWithFallback($email, $otp, $deliveryNote)) {
                $actionText = $_SESSION["otp_flow"]["context"] === "signup"
                    ? " New OTP sent. Verify the code to finish sign up."
                    : ($_SESSION["otp_flow"]["context"] === "password_reset"
                        ? " New OTP sent. Enter the code below to reset your password."
                        : " New OTP sent. Enter the code below to log in.");
                setFlashMessage($message, $messageType, $deliveryNote . $actionText, "success");
            } else {
                setFlashMessage($message, $messageType, $deliveryNote, "error");
            }
        }
    }

    if (isset($_POST["reset_password"])) {
        $resetFlow = $_SESSION["password_reset"] ?? null;
        $newPassword = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        if (!$resetFlow || time() > ($resetFlow["expires_at"] ?? 0)) {
            clearPasswordResetFlow();
            setFlashMessage($message, $messageType, "Password reset session expired. Request a new OTP.", "error");
        } elseif ($newPassword !== $confirmPassword) {
            setFlashMessage($message, $messageType, "Passwords do not match.", "error");
        } elseif (strlen($newPassword) < AppConfig::MIN_PASSWORD_LENGTH) {
            setFlashMessage($message, $messageType, "Password must be at least " . AppConfig::MIN_PASSWORD_LENGTH . " characters.", "error");
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $clientId = (int) $resetFlow["id"];
            $stmt = $conn->prepare("UPDATE clients SET password = ? WHERE id = ? LIMIT 1");
            $stmt->bind_param("si", $hashedPassword, $clientId);

            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                clearPasswordResetFlow();
                setFlashMessage($message, $messageType, "Password changed successfully. You can log in now.", "success");
            } else {
                setFlashMessage($message, $messageType, "Could not update password. Please try again.", "error");
            }

            $stmt->close();
        }
    }

    if (isset($_POST["google_signin"])) {
        $credential = $_POST["google_credential"] ?? "";

        if ($credential === "") {
            setFlashMessage($message, $messageType, "Google sign-in did not return a credential.", "error");
        } elseif ($googleClientId === AppConfig::GOOGLE_CLIENT_ID) {
            setFlashMessage($message, $messageType, "Add your Google client ID in login.php before using Google sign-in.", "error");
        } else {
            [$verified, $googleData] = fetchGoogleProfileFromToken($credential);

            if (!$verified) {
                setFlashMessage($message, $messageType, $googleData, "error");
            } elseif (($googleData["aud"] ?? "") !== $googleClientId) {
                setFlashMessage($message, $messageType, "Google token audience does not match your app.", "error");
            } elseif (($googleData["email_verified"] ?? "false") !== "true") {
                setFlashMessage($message, $messageType, "Google account email is not verified.", "error");
            } else {
                $email = $googleData["email"] ?? "";
                $name = trim($googleData["name"] ?? "");
                $username = $name !== "" ? $name : buildUsernameFromEmail($email);

                $stmt = $conn->prepare("SELECT id, username FROM clients WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($id, $existingUsername);
                    $stmt->fetch();
                    loginUser($id, $existingUsername);
                    $stmt->close();
                    header("Location: index.php");
                    exit;
                }
                $stmt->close();

                $generatedPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $insert = $conn->prepare("INSERT INTO clients (username, email, password) VALUES (?, ?, ?)");
                $insert->bind_param("sss", $username, $email, $generatedPassword);

                if ($insert->execute()) {
                    loginUser($insert->insert_id, $username);
                    $insert->close();
                    header("Location: index.php");
                    exit;
                }

                setFlashMessage($message, $messageType, "Google sign-in could not create the account: " . $insert->error, "error");
                $insert->close();
            }
        }
    }
}

$otpFlow = $_SESSION["otp_flow"] ?? null;
$showOtpPanel = is_array($otpFlow);
$isSignupOtp = $showOtpPanel && $otpFlow["context"] === "signup";
$isPasswordResetOtp = $showOtpPanel && $otpFlow["context"] === "password_reset";
$otpTarget = $showOtpPanel ? ($otpFlow["payload"]["email"] ?? "") : "";
$otpDeliveryMode = $showOtpPanel ? ($otpFlow["delivery_mode"] ?? "pending") : "pending";
$otpDeliveryNote = $showOtpPanel ? ($otpFlow["delivery_note"] ?? "") : "";
$fallbackOtp = $otpDeliveryMode === "fallback" ? normalizeOtpCode($otpFlow["otp"] ?? "") : "";
$passwordResetFlow = $_SESSION["password_reset"] ?? null;
$showPasswordResetPanel = is_array($passwordResetFlow) && time() <= ($passwordResetFlow["expires_at"] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Signup</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        :root {
            --primary: #00b386;
            --primary-dark: #008c69;
            --panel: rgba(255, 255, 255, 0.14);
            --panel-solid: rgba(12, 20, 28, 0.82);
            --border: rgba(255, 255, 255, 0.16);
            --text: #f5fbff;
            --muted: #bfd2db;
            --danger: #ff6b6b;
            --success: #88f0c2;
            --input-bg: rgba(255, 255, 255, 0.08);
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
                radial-gradient(circle at top left, rgba(0, 179, 134, 0.25), transparent 35%),
                radial-gradient(circle at bottom right, rgba(255, 196, 0, 0.18), transparent 30%),
                linear-gradient(135deg, #071018 0%, #102331 45%, #09141d 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .shell {
            width: min(1120px, 100%);
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 28px;
            overflow: hidden;
            backdrop-filter: blur(16px);
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.35);
        }

        .hero {
            padding: 56px 48px;
            background:
                linear-gradient(180deg, rgba(0, 0, 0, 0.06), rgba(0, 0, 0, 0.36)),
                url("https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1600&q=80") center/cover;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 20px;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(4, 12, 18, 0.25), rgba(4, 12, 18, 0.82));
        }

        .hero > * {
            position: relative;
            z-index: 1;
        }

        .badge {
            width: fit-content;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 13px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(2.2rem, 4vw, 4rem);
            line-height: 1.05;
        }

        .hero p {
            margin: 0;
            color: #d7e7ef;
            max-width: 480px;
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .hero ul {
            margin: 8px 0 0;
            padding-left: 20px;
            color: #e5f3f8;
            line-height: 1.8;
        }

        .auth {
            background: var(--panel-solid);
            padding: 36px;
        }

        .panel-switch {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }

        .switch-btn {
            flex: 1;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            padding: 12px 14px;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 600;
        }

        .switch-btn.active {
            background: rgba(0, 179, 134, 0.18);
            border-color: rgba(0, 179, 134, 0.42);
        }

        .message {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            font-size: 14px;
            line-height: 1.5;
            border: 1px solid transparent;
        }

        .message.info {
            background: rgba(92, 155, 196, 0.14);
            border-color: rgba(92, 155, 196, 0.25);
        }

        .message.success {
            background: rgba(136, 240, 194, 0.12);
            border-color: rgba(136, 240, 194, 0.28);
            color: var(--success);
        }

        .message.error {
            background: rgba(255, 107, 107, 0.12);
            border-color: rgba(255, 107, 107, 0.28);
            color: #ffd5d5;
        }

        .card {
            display: none;
            animation: fadeUp 0.35s ease;
        }

        .card.active {
            display: block;
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 1.8rem;
        }

        .card p {
            margin: 0 0 20px;
            color: var(--muted);
            line-height: 1.55;
        }

        .field-group {
            display: grid;
            gap: 12px;
        }

        input {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: var(--input-bg);
            color: var(--text);
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 15px;
            outline: none;
        }

        input::placeholder {
            color: #b9c6ce;
        }

        input:focus {
            border-color: rgba(0, 179, 134, 0.55);
            box-shadow: 0 0 0 3px rgba(0, 179, 134, 0.12);
        }

        .btn {
            border: none;
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #00cc99 100%);
            color: #03241b;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--muted);
            margin: 18px 0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .otp-box {
            margin-top: 24px;
            padding: 18px;
            border-radius: 18px;
            border: 1px solid rgba(0, 179, 134, 0.22);
            background: rgba(0, 179, 134, 0.08);
        }

        .otp-box h3 {
            margin: 0 0 8px;
        }

        .otp-box p {
            margin-bottom: 14px;
        }

        .mini-note {
            margin-top: 12px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .form-link {
            margin: 12px 0 0;
            padding: 0;
            border: 0;
            background: transparent;
            color: #9ce9cb;
            cursor: pointer;
            font: inherit;
            font-weight: 600;
            text-align: left;
        }

        .form-link:hover {
            color: #c8f7e3;
        }

        .otp-help {
            margin-top: 12px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(255, 196, 0, 0.12);
            border: 1px solid rgba(255, 196, 0, 0.25);
            color: #ffe6a0;
            line-height: 1.55;
            font-size: 14px;
        }

        .otp-code {
            display: inline-block;
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px dashed rgba(255, 255, 255, 0.28);
            color: #ffffff;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.18em;
        }

        .otp-actions {
            display: flex;
            gap: 12px;
            margin-top: 12px;
        }

        .otp-actions form {
            flex: 1;
        }

        .logout-link {
            display: inline-block;
            margin-top: 18px;
            color: #9ce9cb;
            text-decoration: none;
        }

        .google-wrap {
            min-height: 42px;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 900px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .hero {
                min-height: 280px;
                padding: 32px 24px;
            }

            .auth {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="hero">
            <span class="badge">Falcons Theater</span>
            <h1>Fast sign in for your next movie night.</h1>
            <p>Use your password, log in with OTP, or continue with your Google account. New sign-ups are protected with email OTP verification.</p>
            <ul>
                <li>Password login for returning users</li>
                <li>Email OTP login without typing a password</li>
                <li>Google account sign-in with automatic account creation</li>
            </ul>
        </section>

        <section class="auth">
            <div class="panel-switch">
                <button type="button" class="switch-btn" data-target="signin-card">Sign In</button>
                <button type="button" class="switch-btn" data-target="signup-card">Sign Up</button>
            </div>

            <?php if ($message !== ""): ?>
                <div class="message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="card" id="signin-card">
                <h2>Welcome back</h2>
                <p>Sign in with your password, request a one-time passcode, or continue with Google.</p>

                <form method="POST" class="field-group">
                    <input type="email" name="login_email" placeholder="Email address" required>
                    <input type="password" name="login_password" placeholder="Password" required>
                    <button class="btn btn-primary" type="submit" name="login">Login with Password</button>
                </form>
                <button type="button" class="form-link" data-target="reset-card">Forgot password?</button>

                <div class="divider">or</div>

                <form method="POST" class="field-group">
                    <input type="email" name="otp_login_email" placeholder="Enter your email for OTP login" required>
                    <button class="btn btn-secondary" type="submit" name="request_login_otp">Send Login OTP</button>
                </form>

                <div class="divider">or</div>

                <div id="googleSignInContainer" class="google-wrap"></div>
                <div class="mini-note">
                    Google sign-in needs your real Google client ID configured near the top of this file.
                </div>
            </div>

            <div class="card" id="signup-card">
                <h2>Create your account</h2>
                <p>We will send an OTP to your email to verify the account before registration completes.</p>

                <form method="POST" class="field-group">
                    <input type="text" name="signup_username" placeholder="Username" required>
                    <input type="email" name="signup_email" placeholder="Email address" required>
                    <input type="password" name="signup_password" placeholder="Password" required>
                    <button class="btn btn-primary" type="submit" name="signup">Sign Up with OTP</button>
                </form>
            </div>

            <div class="card" id="reset-card">
                <h2>Reset password</h2>
                <p>Enter your account email. We will send an OTP before you choose a new password.</p>

                <form method="POST" class="field-group">
                    <input type="email" name="reset_email" placeholder="Account email address" required>
                    <button class="btn btn-primary" type="submit" name="request_password_reset">Send Reset OTP</button>
                </form>
                <button type="button" class="form-link" data-target="signin-card">Back to sign in</button>
            </div>

            <?php if ($showOtpPanel): ?>
                <div class="otp-box">
                    <h3>
                        <?php if ($isSignupOtp): ?>
                            Verify sign-up OTP
                        <?php elseif ($isPasswordResetOtp): ?>
                            Verify reset OTP
                        <?php else: ?>
                            Verify login OTP
                        <?php endif; ?>
                    </h3>
                    <p>Enter the 6-digit code sent to <strong><?= htmlspecialchars($otpTarget) ?></strong>.</p>
                    <?php if ($otpDeliveryMode === "fallback"): ?>
                        <div class="otp-help">
                            Email sending is failing on the server, so this page is using the development fallback OTP instead of inbox delivery.
                            <div class="otp-code"><?= htmlspecialchars($fallbackOtp) ?></div>
                        </div>
                    <?php elseif ($otpDeliveryNote !== ""): ?>
                        <div class="otp-help"><?= htmlspecialchars($otpDeliveryNote) ?></div>
                    <?php endif; ?>
                    <form method="POST" class="field-group">
                        <input type="text" name="otp_code" placeholder="Enter OTP" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required>
                        <button class="btn btn-primary" type="submit" name="verify_otp">Verify OTP</button>
                    </form>
                    <div class="otp-actions">
                        <form method="POST">
                            <button class="btn btn-secondary" type="submit" name="resend_otp">Resend OTP</button>
                        </form>
                    </div>
                    <div class="mini-note">OTP expires in 10 minutes. Request a fresh code if it expires.</div>
                </div>
            <?php endif; ?>

            <?php if ($showPasswordResetPanel): ?>
                <div class="otp-box">
                    <h3>Create new password</h3>
                    <p>Choose a new password for <strong><?= htmlspecialchars($passwordResetFlow["email"] ?? "") ?></strong>.</p>
                    <form method="POST" class="field-group">
                        <input type="password" name="new_password" placeholder="New password" minlength="<?= AppConfig::MIN_PASSWORD_LENGTH ?>" required>
                        <input type="password" name="confirm_password" placeholder="Confirm new password" minlength="<?= AppConfig::MIN_PASSWORD_LENGTH ?>" required>
                        <button class="btn btn-primary" type="submit" name="reset_password">Change Password</button>
                    </form>
                    <div class="mini-note">This reset session expires in 10 minutes.</div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION["user_id"])): ?>
                <a class="logout-link" href="login.php?logout=1">Logout</a>
            <?php endif; ?>
        </section>
    </div>

    <form method="POST" id="googleCredentialForm" style="display:none;">
        <input type="hidden" name="google_credential" id="google_credential">
        <input type="hidden" name="google_signin" value="1">
    </form>

    <script>
        const signupPreferred = <?= $isSignupOtp ? "true" : "false" ?>;
        const resetPreferred = <?= ($isPasswordResetOtp || $showPasswordResetPanel) ? "true" : "false" ?>;
        const defaultCardId = signupPreferred ? "signup-card" : (resetPreferred ? "reset-card" : "signin-card");
        const switchButtons = document.querySelectorAll(".switch-btn");
        const cards = document.querySelectorAll(".card");

        function setActiveCard(cardId) {
            cards.forEach((card) => {
                card.classList.toggle("active", card.id === cardId);
            });

            switchButtons.forEach((button) => {
                button.classList.toggle("active", button.dataset.target === cardId);
            });
        }

        switchButtons.forEach((button) => {
            button.addEventListener("click", () => setActiveCard(button.dataset.target));
        });

        document.querySelectorAll(".form-link[data-target]").forEach((button) => {
            button.addEventListener("click", () => setActiveCard(button.dataset.target));
        });

        setActiveCard(defaultCardId);

        window.handleGoogleCredentialResponse = function handleGoogleCredentialResponse(response) {
            document.getElementById("google_credential").value = response.credential;
            document.getElementById("googleCredentialForm").submit();
        };

        window.addEventListener("load", () => {
            const clientId = <?= json_encode($googleClientId) ?>;
            if (!window.google || clientId === "YOUR_GOOGLE_CLIENT_ID") {
                return;
            }

            google.accounts.id.initialize({
                client_id: clientId,
                callback: handleGoogleCredentialResponse
            });

            google.accounts.id.renderButton(
                document.getElementById("googleSignInContainer"),
                {
                    theme: "outline",
                    size: "large",
                    shape: "pill",
                    text: "continue_with",
                    width: 280
                }
            );
        });
    </script>
</body>
</html>
