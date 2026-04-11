<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cn_redirect('../index.php');
}

$token = $_POST['csrf_token'] ?? null;
if (!cn_csrf_verify(is_string($token) ? $token : null)) {
    cn_flash_set('auth_error', 'Invalid session. Please try again.');
    cn_redirect('../index.php?register=1');
}

$email = trim((string) ($_POST['email'] ?? ''));
$displayName = trim((string) ($_POST['display_name'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$password2 = (string) ($_POST['password_confirm'] ?? '');
$next = cn_safe_redirect_target(isset($_POST['next']) ? (string) $_POST['next'] : null, 'dashboard.php');

if ($displayName === '' || strlen($displayName) > 120) {
    cn_flash_set('auth_error', 'Please enter a display name (max 120 characters).');
    cn_redirect('../index.php?register=1');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    cn_flash_set('auth_error', 'Please enter a valid email address.');
    cn_redirect('../index.php?register=1');
}

if (strlen($password) < 8) {
    cn_flash_set('auth_error', 'Password must be at least 8 characters.');
    cn_redirect('../index.php?register=1');
}

if ($password !== $password2) {
    cn_flash_set('auth_error', 'Passwords do not match.');
    cn_redirect('../index.php?register=1');
}

$pdo = cn_pdo();
$check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->execute([$email]);
if ($check->fetch()) {
    cn_flash_set('auth_error', 'An account with that email already exists.');
    cn_redirect('../index.php?register=1');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$ins = $pdo->prepare(
    'INSERT INTO users (email, password_hash, display_name, role) VALUES (?, ?, ?, \'user\')'
);
$ins->execute([$email, $hash, $displayName]);

cn_login_user((int) $pdo->lastInsertId());
cn_redirect('../' . $next);
