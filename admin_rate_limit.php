<?php
/**
 * Admin authentication rate limiting.
 * Uses a server-side JSON store outside the web root when possible.
 */

function admin_rate_limit_store_path(): string
{
    $configured = getenv('FALCONS_RATE_LIMIT_DIR');
    if ($configured && is_dir($configured) && is_writable($configured)) {
        return rtrim($configured, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'admin_login_limits.json';
    }

    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'falcons_admin_login_limits.json';
}

function admin_rate_limit_key(string $username): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', strtolower(trim($username)) . '|' . $ip);
}

function admin_rate_limit_check(string $username): array
{
    $path = admin_rate_limit_store_path();
    $fp = @fopen($path, 'c+');
    if (!$fp) {
        // Fail closed for authentication abuse protection.
        return [false, 900];
    }

    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) $data = [];

    $now = time();
    foreach ($data as $key => $entry) {
        if (!is_array($entry) || ($entry['reset_at'] ?? 0) < $now - 3600) {
            unset($data[$key]);
        }
    }

    $key = admin_rate_limit_key($username);
    $entry = $data[$key] ?? ['failures' => 0, 'reset_at' => $now + 900];
    if (($entry['reset_at'] ?? 0) <= $now) {
        $entry = ['failures' => 0, 'reset_at' => $now + 900];
    }

    $allowed = ($entry['failures'] ?? 0) < 5;
    $retryAfter = max(1, ($entry['reset_at'] ?? ($now + 900)) - $now);

    if (!$allowed) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return [false, $retryAfter];
    }

    flock($fp, LOCK_UN);
    fclose($fp);
    return [true, 0];
}

function admin_rate_limit_failure(string $username): void
{
    $path = admin_rate_limit_store_path();
    $fp = @fopen($path, 'c+');
    if (!$fp) return;

    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) $data = [];

    $now = time();
    $key = admin_rate_limit_key($username);
    $entry = $data[$key] ?? ['failures' => 0, 'reset_at' => $now + 900];
    if (($entry['reset_at'] ?? 0) <= $now) {
        $entry = ['failures' => 0, 'reset_at' => $now + 900];
    }
    $entry['failures'] = min(5, ((int) $entry['failures']) + 1);
    $data[$key] = $entry;

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function admin_rate_limit_reset(string $username): void
{
    $path = admin_rate_limit_store_path();
    $fp = @fopen($path, 'c+');
    if (!$fp) return;

    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) $data = [];
    unset($data[admin_rate_limit_key($username)]);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}
