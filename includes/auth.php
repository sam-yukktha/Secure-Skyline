<?php
declare(strict_types=1);

function login_user(string $role, array $record): void
{
    session_regenerate_id(true);
    $_SESSION['auth'] = [
        'id' => (int) $record['id'],
        'username' => (string) $record['username'],
        'name' => (string) $record['name'],
        'role' => $role,
    ];
}

function user(): ?array
{
    $auth = $_SESSION['auth'] ?? null;
    return is_array($auth) ? $auth : null;
}

function is_logged_in(): bool
{
    return user() !== null;
}

function is_role(string $role): bool
{
    return (user()['role'] ?? null) === $role;
}

function require_role(string $role): void
{
    if (!is_role($role)) {
        flash('error', 'Please sign in with the correct account to view that page.');
        redirect($role === 'owner' ? 'index.php' : 'doctor-login.php');
    }
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please sign in to continue.');
        redirect('index.php');
    }
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function can_access_record(int $recordId): bool
{
    $auth = user();
    if (!$auth) {
        return false;
    }
    if ($auth['role'] === 'owner') {
        $stmt = db()->prepare('SELECT 1 FROM medical_records WHERE id = ? AND owner_id = ?');
        $stmt->execute([$recordId, $auth['id']]);
        return (bool) $stmt->fetchColumn();
    }
    $stmt = db()->prepare(
        "SELECT 1 FROM access_requests ar
         JOIN reports r ON r.id = ar.report_id
         WHERE r.medical_record_id = ? AND ar.user_id = ? AND ar.status = 'approved'"
    );
    $stmt->execute([$recordId, $auth['id']]);
    return (bool) $stmt->fetchColumn();
}
