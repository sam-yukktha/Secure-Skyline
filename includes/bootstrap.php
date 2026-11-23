<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function app_config(?string $key = null): mixed
{
    static $config = null;
    $config ??= require dirname(__DIR__) . '/config/app.php';
    return $key === null ? $config : ($config[$key] ?? null);
}

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/skyline.php';
require_once __DIR__ . '/health.php';

set_exception_handler(static function (Throwable $exception): void {
    error_log('Unhandled Skyline application error: ' . $exception->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Application error: ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    $message = $exception instanceof RuntimeException
        ? $exception->getMessage()
        : 'An unexpected application error occurred. Check the Apache error log.';
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Application error</title><body style="font:16px Arial;max-width:760px;margin:50px auto;padding:20px">';
    echo '<h1>Application unavailable</h1><p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
    echo '<p><a href="' . htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') . '">Return to the application</a></p></body></html>';
});

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:; base-uri 'self'; form-action 'self'");
}
