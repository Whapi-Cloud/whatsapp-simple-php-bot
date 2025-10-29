<?php

// This script downloads the CA certificates bundle (cacert.pem)
// into the project's config directory. It is designed to be resilient
// and never fail the Composer install/update if the download is not possible.

declare(strict_types=1);

/**
 * Prints a message to STDERR.
 */
function warn(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    logIfEnabled('[WARN] ' . $message);
}

/**
 * Prints a message to STDOUT.
 */
function info(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
    logIfEnabled('[INFO] ' . $message);
}

/**
 * Writes a log line to file if CACERT_LOG env var is set to a writable path.
 */
function logIfEnabled(string $line): void
{
    static $logPath = null;
    if ($logPath === null) {
        $env = getenv('CACERT_LOG');
        $logPath = is_string($env) && $env !== '' ? $env : null; // opt-in only
    }
    if ($logPath === null) {
        return; // logging disabled
    }
    $dir = dirname($logPath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $timestamp = date('c');
    @file_put_contents($logPath, $timestamp . ' ' . $line . PHP_EOL, FILE_APPEND);
}

/**
 * Attempt to download a URL and return its contents or null on failure.
 * Tries cURL first (if available), then falls back to file_get_contents.
 */
function downloadUrl(string $url, int $timeoutSeconds = 20): ?string
{
    // Prefer cURL if the extension exists
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $data = curl_exec($ch);
            $err  = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($data !== false && $code >= 200 && $code < 300) {
                return (string) $data;
            }
            warn("Warning: cURL failed to download CA bundle (HTTP {$code}): {$err}");
        }
    }

    // Fallback: streams
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'follow_location' => 1,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        warn('Warning: failed to download CA bundle via file_get_contents.');
        return null;
    }
    return (string) $data;
}

/**
 * Writes data atomically to a file path (best-effort on Windows as well).
 */
function atomicWrite(string $path, string $data): bool
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
            warn('Warning: failed to create directory: ' . $dir);
            return false;
        }
    }
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $data) === false) {
        warn('Warning: failed to write temporary file: ' . $tmp);
        return false;
    }
    // On Windows rename() will overwrite only if destination does not exist
    if (file_exists($path)) {
        @unlink($path);
    }
    if (!@rename($tmp, $path)) {
        // Attempt copy fallback
        if (!@copy($tmp, $path)) {
            @unlink($tmp);
            warn('Warning: failed to move temporary file to destination: ' . $path);
            return false;
        }
        @unlink($tmp);
    }
    return true;
}

// Main
try {
    // Allow skipping via env: CACERT_DOWNLOAD=0|false|no|off
    $downloadToggle = strtolower((string) (getenv('CACERT_DOWNLOAD') ?: '1'));
    if (in_array($downloadToggle, ['0', 'false', 'no', 'off'], true)) {
        info('Skipping cacert.pem download as requested by CACERT_DOWNLOAD env.');
        exit(0);
    }

    // Allow overriding URL via env CACERT_URL
    $urlEnv   = getenv('CACERT_URL');
    $url      = is_string($urlEnv) && $urlEnv !== '' ? $urlEnv : 'https://curl.se/ca/cacert.pem';
    $destPath = __DIR__ . '/../config/cacert.pem';

    info('Ensuring CA bundle is present...');
    $data = downloadUrl($url);
    if ($data === null) {
        warn('Warning: could not download cacert.pem. HTTPS requests may fail on some Windows environments.');
        // Do not fail Composer; exit success
        exit(0);
    }

    if (!atomicWrite($destPath, $data)) {
        warn('Warning: failed to save cacert.pem to ' . $destPath);
        exit(0);
    }

    info('Downloaded CA bundle to ' . $destPath);
} catch (Throwable $e) {
    // Never fail the installation because of this helper
    warn('Warning: unexpected error while preparing cacert.pem: ' . $e->getMessage());
    exit(0);
}

exit(0);


