<?php

declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function cn_user_id(): ?int
{
    $id = $_SESSION['cn_user_id'] ?? null;
    return is_int($id) || (is_string($id) && ctype_digit($id)) ? (int) $id : null;
}

function cn_login_user(int $userId): void
{
    $_SESSION['cn_user_id'] = $userId;
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }
}

function cn_logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

function cn_csrf_token(): string
{
    if (empty($_SESSION['cn_csrf'])) {
        $_SESSION['cn_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['cn_csrf'];
}

function cn_csrf_verify(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['cn_csrf'])
        && hash_equals($_SESSION['cn_csrf'], $token);
}

/**
 * @return array<string, mixed>|null
 */
function cn_current_user(?PDO $pdo = null): ?array
{
    $uid = cn_user_id();
    if ($uid === null) {
        return null;
    }
    $pdo = $pdo ?? cn_pdo();
    $st = $pdo->prepare(
        'SELECT id, email, display_name, avatar_url, bio, role, created_at FROM users WHERE id = ? LIMIT 1'
    );
    $st->execute([$uid]);
    $row = $st->fetch();
    return $row ?: null;
}

function cn_safe_redirect_target(?string $next, string $default): string
{
    if ($next === null || $next === '') {
        return $default;
    }
    $next = trim($next);
    if (str_contains($next, "\n") || str_contains($next, "\r")) {
        return $default;
    }
    // Relative *.php only (incl. hyphens e.g. recipe-details.php), optional ?query / #fragment; no traversal
    if (str_contains($next, '..')) {
        return $default;
    }
    if (!preg_match('~^(?:[a-zA-Z0-9_.-]+/)*[a-zA-Z0-9_.-]+\.php(?:\?[^\x00-\x1f]*)?(?:#[^\x00-\x1f]*)?$~', $next)) {
        return $default;
    }

    return $next;
}

function cn_redirect(string $location): never
{
    header('Location: ' . $location, true, 302);
    exit;
}

function cn_require_login(string $redirectUrl = 'index.php?login=1'): void
{
    if (cn_user_id() === null) {
        cn_redirect($redirectUrl);
    }
}

function cn_flash_set(string $key, string $message): void
{
    $_SESSION['cn_flash'][$key] = $message;
}

function cn_flash_get(string $key): ?string
{
    if (!isset($_SESSION['cn_flash']) || !isset($_SESSION['cn_flash'][$key])) {
        return null;
    }
    $msg = $_SESSION['cn_flash'][$key];
    unset($_SESSION['cn_flash'][$key]);
    return is_string($msg) ? $msg : null;
}
