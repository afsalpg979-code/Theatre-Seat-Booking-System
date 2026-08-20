<?php
/**
 * Central CSRF protection for state-changing requests.
 *
 * Uses a session-bound token for normal HTML forms and also validates the
 * Origin/Referer for browser requests. JSON/AJAX clients can send the token
 * in X-CSRF-Token.
 */
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

function csrf_origin_is_valid(): bool
{
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    $referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));

    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return false;
    }

    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        $originPort = parse_url($origin, PHP_URL_PORT);
        $requestPort = (int)($_SERVER['SERVER_PORT'] ?? 0);
        if (!is_string($originHost) || !hash_equals(strtolower($host), strtolower($originHost))) {
            return false;
        }
        if ($originPort !== null && $requestPort > 0 && (int)$originPort !== $requestPort) {
            return false;
        }
        return true;
    }

    if ($referer !== '') {
        $refererHost = parse_url($referer, PHP_URL_HOST);
        return is_string($refererHost) && hash_equals(strtolower($host), strtolower($refererHost));
    }

    // Requests without Origin/Referer must still provide the session token.
    return false;
}

function csrf_validate_request(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $provided = (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    $stored = (string)($_SESSION['_csrf_token'] ?? '');

    $tokenValid = $stored !== '' && $provided !== '' && hash_equals($stored, $provided);
    if (!$tokenValid || !csrf_origin_is_valid()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Forbidden');
    }
}

csrf_token();
csrf_validate_request();
