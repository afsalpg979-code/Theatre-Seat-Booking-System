<?php
/**
 * Server-side OTP abuse protection.
 * Limits verification attempts and OTP issuance independently.
 */

function otp_limit_path(): string
{
    $dir = getenv('FALCONS_RATE_LIMIT_DIR');
    if ($dir && is_dir($dir) && is_writable($dir)) {
        return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'otp_limits.json';
    }
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'falcons_otp_limits.json';
}

function otp_limit_key(string $scope, string $identity): string
{
    return hash('sha256', $scope . '|' . strtolower(trim($identity)) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function otp_limit_read(): array
{
    $path = otp_limit_path();
    $fp = @fopen($path, 'c+');
    if (!$fp) return [null, null, []];
    flock($fp, LOCK_EX);
    $data = json_decode(stream_get_contents($fp) ?: '{}', true);
    if (!is_array($data)) $data = [];
    return [$fp, $path, $data];
}

function otp_limit_write($fp, array $data): void
{
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function otp_limit_check(string $scope, string $identity, int $max, int $window): array
{
    [$fp, $path, $data] = otp_limit_read();
    if (!$fp) return [false, $window];
    $now = time();
    $key = otp_limit_key($scope, $identity);
    $entry = $data[$key] ?? ['count' => 0, 'reset_at' => $now + $window];
    if (($entry['reset_at'] ?? 0) <= $now) $entry = ['count' => 0, 'reset_at' => $now + $window];
    $allowed = ((int)$entry['count']) < $max;
    $retry = max(1, ((int)$entry['reset_at']) - $now);
    otp_limit_write($fp, $data);
    return [$allowed, $retry];
}

function otp_limit_increment(string $scope, string $identity, int $window): void
{
    [$fp, $path, $data] = otp_limit_read();
    if (!$fp) return;
    $now = time();
    $key = otp_limit_key($scope, $identity);
    $entry = $data[$key] ?? ['count' => 0, 'reset_at' => $now + $window];
    if (($entry['reset_at'] ?? 0) <= $now) $entry = ['count' => 0, 'reset_at' => $now + $window];
    $entry['count'] = min(100, ((int)$entry['count']) + 1);
    $data[$key] = $entry;
    otp_limit_write($fp, $data);
}

function otp_limit_reset(string $scope, string $identity): void
{
    [$fp, $path, $data] = otp_limit_read();
    if (!$fp) return;
    unset($data[otp_limit_key($scope, $identity)]);
    otp_limit_write($fp, $data);
}
