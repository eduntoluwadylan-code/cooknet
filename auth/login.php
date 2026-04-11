<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cn_redirect('../index.php');
}

$token = $_POST['csrf_token'] ?? null;
if (!cn_csrf_verify(is_string($token) ? $token : null)) {
    cn_flash_set('auth_error', 'Invalid session. Please try again.');
    cn_redirect('../index.php?login=1');
}

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$next = cn_safe_redirect_target(isset($_POST['next']) ? (string) $_POST['next'] : null, 'dashboard.php');

if ($email === '' || $password === '') {
    cn_flash_set('auth_error', 'Email and password are required.');
    cn_redirect('../index.php?login=1');
}

$pdo = cn_pdo();
$st = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
$st->execute([$email]);
$row = $st->fetch();

if (!$row || !password_verify($password, (string) $row['password_hash'])) {
    cn_flash_set('auth_error', 'Invalid email or password.');
    cn_redirect('../index.php?login=1');
}

cn_login_user((int) $row['id']);
cn_redirect('../' . $next);
