<?php
/** Central CSRF protection for state-changing requests. */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_cookie_token(): string
{
    $token = csrf_token();
    if (empty($_COOKIE['_csrf'])) {
        setcookie('_csrf', $token, [
            'expires' => time() + 86400,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Strict',
        ]);
    }
    return $token;
}

function csrf_origin_is_valid(): bool
{
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    $referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host);

    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        return is_string($originHost) && hash_equals($host, strtolower($originHost));
    }

    if ($referer !== '') {
        $refererHost = parse_url($referer, PHP_URL_HOST);
        return is_string($refererHost) && hash_equals($host, strtolower($refererHost));
    }

    return false;
}

function csrf_validate_request(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $stored = (string)($_SESSION['_csrf_token'] ?? '');
    $provided = (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    $cookie = (string)($_COOKIE['_csrf'] ?? '');

    // Prefer an explicit form/header token. The Strict cookie provides a
    // compatibility fallback for existing forms that have not yet been edited.
    $tokenValid = $stored !== '' && (
        ($provided !== '' && hash_equals($stored, $provided)) ||
        ($cookie !== '' && hash_equals($stored, $cookie))
    );

    if (!$tokenValid || !csrf_origin_is_valid()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Forbidden');
    }
}

csrf_cookie_token();
csrf_validate_request();
