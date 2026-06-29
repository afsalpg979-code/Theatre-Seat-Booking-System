<?php
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/logger.php')) {
    require_once __DIR__ . '/logger.php';
}

function theaterMailConfig()
{
    $config = require __DIR__ . '/mail_config.php';

    if (!is_array($config)) {
        return [];
    }

    foreach (['host', 'username', 'password', 'from_email', 'from_name', 'secure', 'auth_type'] as $key) {
        if (isset($config[$key]) && is_string($config[$key])) {
            $config[$key] = trim($config[$key]);
        }
    }

    if (isset($config['password']) && is_string($config['password'])) {
        // Gmail app passwords are often copied with spaces for readability.
        $config['password'] = str_replace(' ', '', $config['password']);
    }

    if (isset($config['port'])) {
        $config['port'] = (int) $config['port'];
    }

    if (empty($config['secure'])) {
        $config['secure'] = ((int) ($config['port'] ?? 587) === 465)
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
    }

    if (!isset($config['timeout']) || (int) $config['timeout'] <= 0) {
        $config['timeout'] = 15;
    } else {
        $config['timeout'] = (int) $config['timeout'];
    }

    return $config;
}

function sanitizeMailConfigForLogging(array $config)
{
    $safe = $config;

    if (!empty($safe['password'])) {
        $safe['password'] = str_repeat('*', max(strlen((string) $safe['password']) - 4, 0))
            . substr((string) $safe['password'], -4);
    }

    return $safe;
}

function theaterMailFailureMessage($errorMessage)
{
    $baseMessage = 'Mailer error: ' . $errorMessage;
    $normalized = strtolower((string) $errorMessage);

    if (
        strpos($normalized, 'authenticate') !== false ||
        strpos($normalized, '535') !== false ||
        strpos($normalized, '534') !== false ||
        strpos($normalized, 'username and password not accepted') !== false
    ) {
        return $baseMessage . ' Gmail rejected SMTP login. Use a 16-character Google app password, keep 2-step verification enabled, and confirm the Gmail address in mail_config.php matches the app-password account.';
    }

    return $baseMessage . ' Check SMTP host, port, encryption, and credentials in mail_config.php.';
}

function logTheaterMailFailure($context, $errorMessage)
{
    if (!class_exists('Logger')) {
        return;
    }

    Logger::error('Mail delivery failed', [
        'context' => $context,
        'error' => $errorMessage,
        'config' => sanitizeMailConfigForLogging(theaterMailConfig()),
    ]);
}

function theaterMailIsConfigured(&$deliveryNote = null)
{
    $config = theaterMailConfig();
    $requiredKeys = ['host', 'port', 'username', 'password', 'from_email', 'from_name'];

    foreach ($requiredKeys as $key) {
        if (!isset($config[$key]) || trim((string) $config[$key]) === '') {
            $deliveryNote = "Mail configuration is incomplete. Update mail_config.php.";
            return false;
        }
    }

    if (
        $config['username'] === 'yourgmail@gmail.com' ||
        $config['password'] === 'your_16_char_app_password' ||
        $config['from_email'] === 'yourgmail@gmail.com'
    ) {
        $deliveryNote = "Update mail_config.php with your Gmail address and app password.";
        return false;
    }

    return true;
}

function createTheaterMailer()
{
    $config = theaterMailConfig();
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = $config['secure'];
    $mail->Port = $config['port'];
    $mail->Timeout = $config['timeout'];
    $mail->SMTPAutoTLS = true;
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->setFrom($config['from_email'], $config['from_name']);

    if (!empty($config['auth_type'])) {
        $mail->AuthType = $config['auth_type'];
    }

    if (!empty($config['debug']) && class_exists('Logger')) {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = static function ($message, $level) {
            Logger::info('SMTP debug', [
                'level' => $level,
                'message' => trim((string) $message),
            ]);
        };
    }

    return $mail;
}

function ensureTicketMailSession()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['ticket_mail_sent']) || !is_array($_SESSION['ticket_mail_sent'])) {
        $_SESSION['ticket_mail_sent'] = [];
    }
}

function wasTicketMailSent(int $bookingId, string $recipient)
{
    ensureTicketMailSession();
    $key = $bookingId . '|' . strtolower(trim($recipient));
    return !empty($_SESSION['ticket_mail_sent'][$key]);
}

function markTicketMailSent(int $bookingId, string $recipient)
{
    ensureTicketMailSession();
    $key = $bookingId . '|' . strtolower(trim($recipient));
    $_SESSION['ticket_mail_sent'][$key] = date('Y-m-d H:i:s');
}

function resolveLoggedInUserEmail(mysqli $conn)
{
    ensureTicketMailSession();

    $userId = intval($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT email FROM clients WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row || empty($row['email'])) {
        return null;
    }

    return trim((string) $row['email']);
}

function buildTicketAbsoluteUrl(int $bookingId)
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    return $scheme . '://' . $host . ($basePath !== '' ? $basePath : '') . '/print1.php?id=' . $bookingId;
}

function formatTicketEmailDate($value)
{
    if (!is_string($value)) {
        return 'Not available';
    }

    $value = trim($value);
    if ($value === '' || $value === '0000-00-00 00:00:00' || $value === '0000-00-00') {
        return 'Not available';
    }

    try {
        $date = new DateTime($value);
        return $date->format('d M Y, h:i A');
    } catch (\Exception $exception) {
        return $value;
    }
}

function sendTicketMail(string $toEmail, array $booking, string $ticketUrl, &$deliveryNote)
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $deliveryNote = 'Customer email is not available for ticket sending.';
        return false;
    }

    if (!theaterMailIsConfigured($deliveryNote)) {
        return false;
    }

    $bookingId = intval($booking['id'] ?? 0);
    if ($bookingId > 0 && wasTicketMailSent($bookingId, $toEmail)) {
        $deliveryNote = 'Ticket email already sent in this session.';
        return true;
    }

    $movie = trim((string) ($booking['movie'] ?? 'Featured Show'));
    $guestName = trim((string) ($booking['name'] ?? 'Guest'));
    $phone = trim((string) ($booking['phone'] ?? 'Not Available'));
    $theater = trim((string) ($booking['theater'] ?? 'T1 - 4K'));
    $seats = trim((string) ($booking['seats'] ?? 'Not Assigned'));
    $amount = number_format((float) ($booking['totalAmount'] ?? 0), 2);
    $showTime = formatTicketEmailDate((string) ($booking['time'] ?? ''));
    $bookingDate = formatTicketEmailDate((string) ($booking['created_at'] ?? date('Y-m-d H:i:s')));
    $bookingCode = 'FT' . str_pad((string) $bookingId, 6, '0', STR_PAD_LEFT);

    $subject = 'Your FALCONS Theater Ticket - ' . $movie;
    $html = '
        <div style="margin:0;padding:24px;background:#10181d;font-family:Arial,sans-serif;color:#211d18;">
            <div style="max-width:640px;margin:0 auto;background:linear-gradient(180deg,#f8eedc 0%,#f4e7d1 100%);border-radius:28px;padding:28px;border:1px solid #f2e7d1;">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                    <div>
                        <div style="font-size:30px;font-weight:700;letter-spacing:2px;line-height:1.1;">FALCONS<br>THEATER</div>
                        <div style="margin-top:10px;color:#6d6354;">Official movie entry pass</div>
                    </div>
                    <div style="padding:12px 18px;border-radius:999px;background:#f1dfae;color:#bb8a16;font-weight:700;letter-spacing:2px;text-transform:uppercase;">Confirmed</div>
                </div>

                <div style="margin-top:26px;font-size:22px;font-weight:700;">' . htmlspecialchars($movie, ENT_QUOTES, 'UTF-8') . '</div>
                <div style="display:inline-block;margin-top:14px;padding:10px 16px;border-radius:999px;background:#f1dfae;color:#bb8a16;font-weight:700;letter-spacing:2px;">' . htmlspecialchars($bookingCode, ENT_QUOTES, 'UTF-8') . '</div>

                <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;margin-top:22px;border-collapse:separate;border-spacing:0 12px;">
                    <tr>
                        <td style="width:50%;padding-right:8px;vertical-align:top;"><div style="background:rgba(255,255,255,0.74);border:1px solid #eadcc6;border-radius:20px;padding:18px;"><div style="font-size:12px;letter-spacing:1px;color:#7f7464;text-transform:uppercase;margin-bottom:10px;">Guest Name</div><div style="font-size:20px;font-weight:700;">' . htmlspecialchars($guestName, ENT_QUOTES, 'UTF-8') . '</div></div></td>
                        <td style="width:50%;padding-left:8px;vertical-align:top;"><div style="background:rgba(255,255,255,0.74);border:1px solid #eadcc6;border-radius:20px;padding:18px;"><div style="font-size:12px;letter-spacing:1px;color:#7f7464;text-transform:uppercase;margin-bottom:10px;">Contact</div><div style="font-size:20px;font-weight:700;">' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</div></div></td>
                    </tr>
                    <tr>
                        <td style="width:50%;padding-right:8px;vertical-align:top;"><div style="background:rgba(255,255,255,0.74);border:1px solid #eadcc6;border-radius:20px;padding:18px;"><div style="font-size:12px;letter-spacing:1px;color:#7f7464;text-transform:uppercase;margin-bottom:10px;">Show Time</div><div style="font-size:20px;font-weight:700;">' . htmlspecialchars($showTime, ENT_QUOTES, 'UTF-8') . '</div></div></td>
                        <td style="width:50%;padding-left:8px;vertical-align:top;"><div style="background:rgba(255,255,255,0.74);border:1px solid #eadcc6;border-radius:20px;padding:18px;"><div style="font-size:12px;letter-spacing:1px;color:#7f7464;text-transform:uppercase;margin-bottom:10px;">Theater</div><div style="font-size:20px;font-weight:700;">' . htmlspecialchars($theater, ENT_QUOTES, 'UTF-8') . '</div></div></td>
                    </tr>
                    <tr>
                        <td style="width:50%;padding-right:8px;vertical-align:top;"><div style="background:rgba(255,255,255,0.74);border:1px solid #eadcc6;border-radius:20px;padding:18px;"><div style="font-size:12px;letter-spacing:1px;color:#7f7464;text-transform:uppercase;margin-bottom:10px;">Seats</div><div style="font-size:20px;font-weight:700;">' . htmlspecialchars($seats, ENT_QUOTES, 'UTF-8') . '</div></div></td>
                        <td style="width:50%;padding-left:8px;vertical-align:top;"><div style="background:rgba(255,255,255,0.74);border:1px solid #eadcc6;border-radius:20px;padding:18px;"><div style="font-size:12px;letter-spacing:1px;color:#7f7464;text-transform:uppercase;margin-bottom:10px;">Total Paid</div><div style="font-size:20px;font-weight:700;">Rs ' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '</div></div></td>
                    </tr>
                </table>

                <div style="margin-top:18px;color:#6d6354;line-height:1.6;">Show this email or open the ticket link below at the theatre entry.</div>
                <div style="margin-top:22px;">
                    <a href="' . htmlspecialchars($ticketUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:13px 22px;border-radius:999px;background:#e9c96f;color:#553c03;text-decoration:none;font-weight:700;">Open Ticket</a>
                </div>
            </div>
        </div>';

    $plainText = "FALCONS Theater Ticket\n"
        . "Movie: {$movie}\n"
        . "Booking Code: {$bookingCode}\n"
        . "Guest Name: {$guestName}\n"
        . "Contact: {$phone}\n"
        . "Show Time: {$showTime}\n"
        . "Theater: {$theater}\n"
        . "Booking Date: {$bookingDate}\n"
        . "Seats: {$seats}\n"
        . "Total Paid: Rs {$amount}\n"
        . "Open Ticket: {$ticketUrl}";

    try {
        $mail = createTheaterMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = $plainText;
        $mail->send();

        if ($bookingId > 0) {
            markTicketMailSent($bookingId, $toEmail);
        }

        $deliveryNote = 'Ticket sent to customer email.';
        return true;
    } catch (\Throwable $e) {
        logTheaterMailFailure('ticket', $e->getMessage());
        $deliveryNote = theaterMailFailureMessage($e->getMessage());
        return false;
    }
}

function sendOtpMail($toEmail, $otp, &$deliveryNote)
{
    if (!theaterMailIsConfigured($deliveryNote)) {
        return false;
    }

    try {
        $mail = createTheaterMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(false);
        $mail->Subject = 'FALCONS Theater OTP';
        $mail->Body = "Your OTP is: {$otp}\nThis code will expire in 10 minutes.";

        $mail->send();
        $deliveryNote = "OTP sent to your email.";
        return true;
    } catch (\Throwable $e) {
        logTheaterMailFailure('otp', $e->getMessage());
        $deliveryNote = theaterMailFailureMessage($e->getMessage());
        return false;
    }
}
