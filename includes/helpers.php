<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string) app_config('base_path'), '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

function post_string(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function validated_number(string $key, float $min, float $max, array &$errors): ?float
{
    $raw = post_string($key);
    if ($raw === '' || !is_numeric($raw)) {
        $errors[] = ucfirst(str_replace('_', ' ', $key)) . ' must be a number.';
        return null;
    }
    $value = (float) $raw;
    if ($value < $min || $value > $max) {
        $errors[] = ucfirst(str_replace('_', ' ', $key)) . " must be between {$min} and {$max}.";
        return null;
    }
    return $value;
}

function current_page(): string
{
    return basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
}

function request_is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function format_datetime(?string $value): string
{
    return $value ? date('d M Y, h:i A', strtotime($value)) : '—';
}

function bmi(float $weightKg, float $heightCm): float
{
    if ($heightCm <= 0) {
        return 0.0;
    }
    return round($weightKg / (($heightCm / 100) ** 2), 1);
}
