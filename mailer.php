<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/logger.php')) {
    require_once __DIR__ . '/logger.php';
}

/*
.
| MAIL CONFIG
.
*/

function theaterMailConfig(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $configFile = __DIR__ . '/mail_config.php';

    if (!file_exists($configFile)) {
        $config = [];
        return $config;
    }

    $loadedConfig = require $configFile;

    if (!is_array($loadedConfig)) {
        $config = [];
        return $config;
    }

    $config = $loadedConfig;

    foreach (
        [
            'host',
            'username',
            'password',
            'from_email',
            'from_name',
            'secure',
            'auth_type'
        ] as $key
    ) {
        if (
            isset($config[$key]) &&
            is_string($config[$key])
        ) {
            $config[$key] = trim($config[$key]);
        }
    }

    /*
     * Gmail App Passwords are often copied with spaces:
     *
     * xxxx xxxx xxxx xxxx
     *
     * Remove whitespace automatically.
     */
    if (
        isset($config['password']) &&
        is_string($config['password'])
    ) {
        $config['password'] = preg_replace(
            '/\s+/',
            '',
            $config['password']
        );
    }

    /*
     * Default SMTP port.
     */
    $config['port'] = isset($config['port'])
        ? (int) $config['port']
        : 587;

    if ($config['port'] <= 0) {
        $config['port'] = 587;
    }

    /*
     * Encryption.
     *
     * 465 = SMTPS
     * 587 = STARTTLS
     */
    if (empty($config['secure'])) {
        $config['secure'] =
            ($config['port'] === 465)
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
    }

    /*
     * SMTP timeout.
     */
    $config['timeout'] = isset($config['timeout'])
        ? (int) $config['timeout']
        : 15;

    if ($config['timeout'] <= 0) {
        $config['timeout'] = 15;
    }

    /*
     * Debug disabled by default.
     */
    $config['debug'] = !empty($config['debug']);

    return $config;
}

/*
.
| SAFE CONFIG FOR LOGGING
.
*/

function sanitizeMailConfigForLogging(
    array $config
): array {
    $safe = $config;

    if (isset($safe['password'])) {
        $safe['password'] = '********';
    }

    return $safe;
}

/*
.
| EMAIL VALIDATION
.
*/

function theaterValidEmail(
    string $email
): bool {
    $email = trim($email);

    if ($email === '') {
        return false;
    }

    if (strlen($email) > 254) {
        return false;
    }

    /*
     * Prevent CRLF/header injection.
     */
    if (
        strpos($email, "\r") !== false ||
        strpos($email, "\n") !== false
    ) {
        return false;
    }

    return filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    ) !== false;
}

/*
.
| MAIL ERROR MESSAGE
.
*/

function theaterMailFailureMessage(
    string $errorMessage
): string {
    $normalized = strtolower($errorMessage);

    /*
     * Gmail authentication errors.
     */
    if (
        strpos($normalized, 'authenticate') !== false ||
        strpos($normalized, 'authentication') !== false ||
        strpos($normalized, '535') !== false ||
        strpos($normalized, '534') !== false ||
        strpos(
            $normalized,
            'username and password not accepted'
        ) !== false
    ) {
        return
            'Mailer authentication failed. ' .
            'Check the Gmail address and 16-character App Password in mail_config.php.';
    }

    /*
     * SMTP connection errors.
     */
    if (
        strpos(
            $normalized,
            'could not connect'
        ) !== false ||
        strpos(
            $normalized,
            'connection refused'
        ) !== false ||
        strpos(
            $normalized,
            'timed out'
        ) !== false ||
        strpos(
            $normalized,
            'network is unreachable'
        ) !== false
    ) {
        return
            'Unable to connect to the mail server. ' .
            'Please check SMTP host, port, and encryption settings.';
    }

    /*
     * TLS / SSL problems.
     */
    if (
        strpos($normalized, 'tls') !== false ||
        strpos($normalized, 'ssl') !== false ||
        strpos($normalized, 'certificate') !== false
    ) {
        return
            'Secure SMTP connection failed. ' .
            'Please check the SMTP encryption and port settings.';
    }

    return
        'Unable to send the email right now. ' .
        'Please try again later.';
}

/*
.
| LOG MAIL FAILURE
.
*/

function logTheaterMailFailure(
    string $context,
    string $errorMessage
): void {
    if (!class_exists('Logger')) {
        return;
    }

    try {
        Logger::error(
            'Mail delivery failed',
            [
                'context' => $context,
                'error' => $errorMessage,
                'config' =>
                    sanitizeMailConfigForLogging(
                        theaterMailConfig()
                    ),
            ]
        );
    } catch (\Throwable $e) {
        /*
         * Logging must never break mail delivery.
         */
    }
}

/*
.
| CHECK MAIL CONFIGURATION
.
*/

function theaterMailIsConfigured(
    &$deliveryNote = null
): bool {
    $config = theaterMailConfig();

    $requiredKeys = [
        'host',
        'port',
        'username',
        'password',
        'from_email',
        'from_name'
    ];

    foreach ($requiredKeys as $key) {
        if (
            !isset($config[$key]) ||
            trim((string) $config[$key]) === ''
        ) {
            $deliveryNote =
                'Mail configuration is incomplete. ' .
                'Please update mail_config.php.';

            return false;
        }
    }

    /*
     * Validate SMTP username.
     */
    if (
        !theaterValidEmail(
            (string) $config['username']
        )
    ) {
        $deliveryNote =
            'SMTP username is not a valid email address.';

        return false;
    }

    /*
     * Validate sender address.
     */
    if (
        !theaterValidEmail(
            (string) $config['from_email']
        )
    ) {
        $deliveryNote =
            'Sender email address is invalid.';

        return false;
    }

    /*
     * Detect placeholder configuration.
     */
    if (
        $config['username'] === 'yourgmail@gmail.com' ||
        $config['password'] === 'your_16_char_app_password' ||
        $config['from_email'] === 'yourgmail@gmail.com'
    ) {
        $deliveryNote =
            'Update mail_config.php with your Gmail address and App Password.';

        return false;
    }

    return true;
}

/*
.
| CREATE MAILER
.
*/

function createTheaterMailer(): PHPMailer
{
    $config = theaterMailConfig();

    if (empty($config)) {
        throw new Exception(
            'Mail configuration is missing or invalid.'
        );
    }

    $mail = new PHPMailer(true);

    /*
     * SMTP.
     */
    $mail->isSMTP();

    $mail->Host =
        (string) $config['host'];

    $mail->SMTPAuth = true;

    $mail->Username =
        (string) $config['username'];

    $mail->Password =
        (string) $config['password'];

    $mail->Port =
        (int) $config['port'];

    $mail->SMTPSecure =
        $config['secure'];

    $mail->Timeout =
        (int) $config['timeout'];

    /*
     * Allow STARTTLS when supported.
     */
    $mail->SMTPAutoTLS = true;

    /*
     * UTF-8.
     */
    $mail->CharSet =
        PHPMailer::CHARSET_UTF8;

    $mail->Encoding =
        PHPMailer::ENCODING_BASE64;

    /*
     * Sender.
     */
    $mail->setFrom(
        (string) $config['from_email'],
        (string) $config['from_name']
    );

    /*
     * Optional authentication type.
     */
    if (
        !empty($config['auth_type'])
    ) {
        $mail->AuthType =
            (string) $config['auth_type'];
    }

    /*
     * SMTP debug.
     *
     * Only use when explicitly enabled.
     */
    if (
        !empty($config['debug']) &&
        class_exists('Logger')
    ) {
        $mail->SMTPDebug =
            SMTP::DEBUG_SERVER;

        $mail->Debugoutput =
            static function (
                $message,
                $level
            ): void {
                try {
                    Logger::info(
                        'SMTP debug',
                        [
                            'level' => $level,
                            'message' =>
                                trim(
                                    (string) $message
                                ),
                        ]
                    );
                } catch (\Throwable $e) {
                    // Ignore logging errors.
                }
            };
    } else {
        $mail->SMTPDebug =
            SMTP::DEBUG_OFF;
    }

    /*
     * Normal email formatting.
     */
    $mail->WordWrap = 78;

    return $mail;
}

/*
.
| SESSION
.
*/

function ensureTicketMailSession(): void
{
    if (
        session_status() !==
        PHP_SESSION_ACTIVE
    ) {
        session_start();
    }

    if (
        !isset(
            $_SESSION['ticket_mail_sent']
        ) ||
        !is_array(
            $_SESSION['ticket_mail_sent']
        )
    ) {
        $_SESSION['ticket_mail_sent'] = [];
    }
}

/*
.
| DUPLICATE MAIL CHECK
.
*/

function wasTicketMailSent(
    int $bookingId,
    string $recipient
): bool {
    ensureTicketMailSession();

    $key =
        $bookingId .
        '|' .
        strtolower(
            trim($recipient)
        );

    return !empty(
        $_SESSION['ticket_mail_sent'][$key]
    );
}

function markTicketMailSent(
    int $bookingId,
    string $recipient
): void {
    ensureTicketMailSession();

    $key =
        $bookingId .
        '|' .
        strtolower(
            trim($recipient)
        );

    $_SESSION['ticket_mail_sent'][$key] =
        date('Y-m-d H:i:s');
}

/*
.
| LOGGED-IN USER EMAIL
.
*/

function resolveLoggedInUserEmail(
    mysqli $conn
) {
    ensureTicketMailSession();

    $userId =
        intval(
            $_SESSION['user_id'] ?? 0
        );

    if ($userId <= 0) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT email
         FROM clients
         WHERE id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param(
        'i',
        $userId
    );

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result =
        $stmt->get_result();

    $row =
        $result
            ? $result->fetch_assoc()
            : null;

    $stmt->close();

    if (
        !$row ||
        empty($row['email'])
    ) {
        return null;
    }

    $email =
        trim(
            (string) $row['email']
        );

    return theaterValidEmail($email)
        ? $email
        : null;
}

/*
.
| BUILD TICKET URL
.
|
| Kept compatible with your existing print1.php setup.
| No mail_config.php / app_url changes required.
|
*/

function buildTicketAbsoluteUrl(
    int $bookingId
): string {
    $isHttps =
        (
            !empty($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] !== 'off'
        )
        ||
        (
            isset($_SERVER['SERVER_PORT']) &&
            (string) $_SERVER['SERVER_PORT'] === '443'
        )
        ||
        (
            isset(
                $_SERVER['HTTP_X_FORWARDED_PROTO']
            ) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] ===
                'https'
        );

    $scheme =
        $isHttps
            ? 'https'
            : 'http';

    $host =
        $_SERVER['HTTP_HOST'] ??
        'localhost';

    /*
     * Protect the Host value from
     * unexpected CRLF characters.
     */
    $host = str_replace(
        ["\r", "\n"],
        '',
        $host
    );

    $basePath =
        rtrim(
            str_replace(
                '\\',
                '/',
                dirname(
                    $_SERVER['SCRIPT_NAME'] ??
                    ''
                )
            ),
            '/'
        );

    if (
        $basePath === '/' ||
        $basePath === '.'
    ) {
        $basePath = '';
    }

    return
        $scheme .
        '://' .
        $host .
        $basePath .
        '/print1.php?id=' .
        intval($bookingId);
}

/*
.
| FORMAT DATE
.
*/

function formatTicketEmailDate(
    $value
): string {
    if (!is_string($value)) {
        return 'Not available';
    }

    $value = trim($value);

    if (
        $value === '' ||
        $value === '0000-00-00' ||
        $value ===
            '0000-00-00 00:00:00'
    ) {
        return 'Not available';
    }

    try {
        $date =
            new DateTime($value);

        return $date->format(
            'd M Y, h:i A'
        );
    } catch (\Throwable $e) {
        return $value;
    }
}

/*
.
| HTML ESCAPE
.
*/

function theaterMailEscape(
    string $value
): string {
    return htmlspecialchars(
        $value,
        ENT_QUOTES |
        ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/*
.
| SEND TICKET MAIL
.
*/

function sendTicketMail(
    string $toEmail,
    array $booking,
    string $ticketUrl,
    &$deliveryNote
): bool {

    $toEmail =
        trim($toEmail);

    if (
        !theaterValidEmail($toEmail)
    ) {
        $deliveryNote =
            'Customer email is not available for ticket sending.';

        return false;
    }

    if (
        !theaterMailIsConfigured(
            $deliveryNote
        )
    ) {
        return false;
    }

    $bookingId =
        intval(
            $booking['id'] ?? 0
        );

    /*
     * Prevent duplicate ticket emails
     * within the current session.
     */
    if (
        $bookingId > 0 &&
        wasTicketMailSent(
            $bookingId,
            $toEmail
        )
    ) {
        $deliveryNote =
            'Ticket email already sent in this session.';

        return true;
    }

    /*
     * Booking information.
     */
    $movie =
        trim(
            (string) (
                $booking['movie']
                ?? 'Featured Show'
            )
        );

    $guestName =
        trim(
            (string) (
                $booking['name']
                ?? 'Guest'
            )
        );

    $phone =
        trim(
            (string) (
                $booking['phone']
                ?? 'Not Available'
            )
        );

    $theater =
        trim(
            (string) (
                $booking['theater']
                ?? 'T1 - 4K'
            )
        );

    $seats =
        trim(
            (string) (
                $booking['seats']
                ?? 'Not Assigned'
            )
        );

    $amount =
        number_format(
            (float) (
                $booking['totalAmount']
                ?? 0
            ),
            2
        );

    $showTime =
        formatTicketEmailDate(
            (string) (
                $booking['time']
                ?? ''
            )
        );

    $bookingDate =
        formatTicketEmailDate(
            (string) (
                $booking['created_at']
                ?? date(
                    'Y-m-d H:i:s'
                )
            )
        );

    $bookingCode =
        'FT' .
        str_pad(
            (string) $bookingId,
            6,
            '0',
            STR_PAD_LEFT
        );

    /*
     * Escape all dynamic HTML values.
     */
    $movieHtml =
        theaterMailEscape($movie);

    $guestHtml =
        theaterMailEscape($guestName);

    $phoneHtml =
        theaterMailEscape($phone);

    $theaterHtml =
        theaterMailEscape($theater);

    $seatsHtml =
        theaterMailEscape($seats);

    $amountHtml =
        theaterMailEscape($amount);

    $showTimeHtml =
        theaterMailEscape($showTime);

    $bookingDateHtml =
        theaterMailEscape($bookingDate);

    $bookingCodeHtml =
        theaterMailEscape($bookingCode);

    $ticketUrlHtml =
        theaterMailEscape($ticketUrl);

    $subject =
        'Your FALCONS Theater Ticket - ' .
        $movie;

    /*
     * Ticket HTML.
     */
    $html = '
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width,initial-scale=1.0">
<title>FALCONS Theater Ticket</title>
</head>

<body style="
    margin:0;
    padding:0;
    background:#10181d;
    font-family:Arial,Helvetica,sans-serif;
">

<div style="
    padding:25px 12px;
    background:#10181d;
">

<div style="
    max-width:640px;
    margin:0 auto;
    background:#f8eedc;
    border-radius:26px;
    padding:28px;
    color:#211d18;
">

<div style="
    font-size:29px;
    font-weight:700;
    letter-spacing:2px;
    line-height:1.1;
">
FALCONS<br>
THEATER
</div>

<div style="
    margin-top:10px;
    color:#6d6354;
">
Official movie entry pass
</div>

<div style="
    margin-top:24px;
    display:inline-block;
    padding:10px 17px;
    border-radius:20px;
    background:#f1dfae;
    color:#8b6816;
    font-weight:700;
    letter-spacing:1px;
">
PAYMENT CONFIRMED
</div>

<div style="
    margin-top:24px;
    font-size:23px;
    font-weight:700;
">
' . $movieHtml . '
</div>

<div style="
    display:inline-block;
    margin-top:12px;
    padding:9px 15px;
    border-radius:20px;
    background:#f1dfae;
    color:#8b6816;
    font-weight:700;
    letter-spacing:2px;
">
' . $bookingCodeHtml . '
</div>

<table
    width="100%"
    cellspacing="0"
    cellpadding="6"
    style="
        margin-top:20px;
        border-collapse:collapse;
    "
>

<tr>

<td width="50%" valign="top">

<div style="
    background:#fffaf2;
    border:1px solid #eadcc6;
    border-radius:18px;
    padding:16px;
">

<div style="
    font-size:11px;
    color:#7f7464;
    text-transform:uppercase;
    letter-spacing:1px;
">
Guest Name
</div>

<div style="
    margin-top:8px;
    font-size:18px;
    font-weight:700;
">
' . $guestHtml . '
</div>

</div>

</td>

<td width="50%" valign="top">

<div style="
    background:#fffaf2;
    border:1px solid #eadcc6;
    border-radius:18px;
    padding:16px;
">

<div style="
    font-size:11px;
    color:#7f7464;
    text-transform:uppercase;
    letter-spacing:1px;
">
Contact
</div>

<div style="
    margin-top:8px;
    font-size:18px;
    font-weight:700;
">
' . $phoneHtml . '
</div>

</div>

</td>

</tr>

<tr>

<td valign="top">

<div style="
    background:#fffaf2;
    border:1px solid #eadcc6;
    border-radius:18px;
    padding:16px;
">

<div style="
    font-size:11px;
    color:#7f7464;
    text-transform:uppercase;
    letter-spacing:1px;
">
Show Time
</div>

<div style="
    margin-top:8px;
    font-size:18px;
    font-weight:700;
">
' . $showTimeHtml . '
</div>

</div>

</td>

<td valign="top">

<div style="
    background:#fffaf2;
    border:1px solid #eadcc6;
    border-radius:18px;
    padding:16px;
">

<div style="
    font-size:11px;
    color:#7f7464;
    text-transform:uppercase;
    letter-spacing:1px;
">
Theater
</div>

<div style="
    margin-top:8px;
    font-size:18px;
    font-weight:700;
">
' . $theaterHtml . '
</div>

</div>

</td>

</tr>

<tr>

<td valign="top">

<div style="
    background:#fffaf2;
    border:1px solid #eadcc6;
    border-radius:18px;
    padding:16px;
">

<div style="
    font-size:11px;
    color:#7f7464;
    text-transform:uppercase;
    letter-spacing:1px;
">
Seats
</div>

<div style="
    margin-top:8px;
    font-size:18px;
    font-weight:700;
">
' . $seatsHtml . '
</div>

</div>

</td>

<td valign="top">

<div style="
    background:#fffaf2;
    border:1px solid #eadcc6;
    border-radius:18px;
    padding:16px;
">

<div style="
    font-size:11px;
    color:#7f7464;
    text-transform:uppercase;
    letter-spacing:1px;
">
Total Paid
</div>

<div style="
    margin-top:8px;
    font-size:18px;
    font-weight:700;
">
Rs ' . $amountHtml . '
</div>

</div>

</td>

</tr>

<tr>

<td colspan="2" valign="top">

<div style="
    background:#fffaf2;
    border:1px solid #eadcc6;
    border-radius:18px;
    padding:16px;
">

<div style="
    font-size:11px;
    color:#7f7464;
    text-transform:uppercase;
    letter-spacing:1px;
">
Booking Date
</div>

<div style="
    margin-top:8px;
    font-size:18px;
    font-weight:700;
">
' . $bookingDateHtml . '
</div>

</div>

</td>

</tr>

</table>

<div style="
    margin-top:20px;
    color:#6d6354;
    line-height:1.6;
">
Please show this email or open your digital
ticket at the theater entrance.
</div>

<div style="margin-top:22px;">

<a
    href="' . $ticketUrlHtml . '"
    style="
        display:inline-block;
        padding:13px 22px;
        border-radius:22px;
        background:#e9c96f;
        color:#553c03;
        text-decoration:none;
        font-weight:700;
    "
>
Open Digital Ticket
</a>

</div>

<div style="
    margin-top:28px;
    padding-top:18px;
    border-top:1px solid #dfd0b8;
    color:#8a7c69;
    font-size:12px;
">
FALCONS THEATER<br>
Please keep this email for your records.
</div>

</div>

</div>

</body>
</html>';

    /*
     * Plain-text fallback.
     */
    $plainText =
        "FALCONS THEATER\n\n" .
        "PAYMENT CONFIRMED\n\n" .
        "Movie: {$movie}\n" .
        "Booking Code: {$bookingCode}\n" .
        "Guest Name: {$guestName}\n" .
        "Contact: {$phone}\n" .
        "Show Time: {$showTime}\n" .
        "Theater: {$theater}\n" .
        "Booking Date: {$bookingDate}\n" .
        "Seats: {$seats}\n" .
        "Total Paid: Rs {$amount}\n\n" .
        "Open Digital Ticket:\n" .
        $ticketUrl;

    /*
     * Send email.
     */
    try {

        $mail =
            createTheaterMailer();

        $mail->addAddress(
            $toEmail
        );

        $mail->isHTML(true);

        $mail->Subject =
            $subject;

        $mail->Body =
            $html;

        $mail->AltBody =
            $plainText;

        $mail->send();

        /*
         * Mark as sent only AFTER
         * successful delivery.
         */
        if ($bookingId > 0) {
            markTicketMailSent(
                $bookingId,
                $toEmail
            );
        }

        $deliveryNote =
            'Ticket sent to customer email.';

        return true;

    } catch (\Throwable $e) {

        logTheaterMailFailure(
            'ticket',
            $e->getMessage()
        );

        $deliveryNote =
            theaterMailFailureMessage(
                $e->getMessage()
            );

        return false;
    }
}

/*
.
| SEND OTP MAIL
.
*/

function sendOtpMail(
    $toEmail,
    $otp,
    &$deliveryNote
): bool {

    $toEmail =
        trim((string) $toEmail);

    $otp =
        trim((string) $otp);

    /*
     * Validate recipient.
     */
    if (
        !theaterValidEmail($toEmail)
    ) {
        $deliveryNote =
            'Please provide a valid email address.';

        return false;
    }

    /*
     * OTP must be numeric.
     */
    if (
        $otp === '' ||
        !preg_match(
            '/^[0-9]{4,8}$/',
            $otp
        )
    ) {
        $deliveryNote =
            'Invalid OTP format.';

        return false;
    }

    /*
     * Check mail configuration.
     */
    if (
        !theaterMailIsConfigured(
            $deliveryNote
        )
    ) {
        return false;
    }

    try {

        $mail =
            createTheaterMailer();

        $mail->addAddress(
            $toEmail
        );

        $mail->isHTML(false);

        $mail->Subject =
            'FALCONS Theater OTP';

        $mail->Body =
            "FALCONS THEATER\n\n" .
            "Your verification code is:\n\n" .
            "{$otp}\n\n" .
            "This code will expire in 10 minutes.\n\n" .
            "If you did not request this code, " .
            "please ignore this email.";

        $mail->send();

        $deliveryNote =
            'OTP sent to your email.';

        return true;

    } catch (\Throwable $e) {

        logTheaterMailFailure(
            'otp',
            $e->getMessage()
        );

        $deliveryNote =
            theaterMailFailureMessage(
                $e->getMessage()
            );

        return false;
    }
}
