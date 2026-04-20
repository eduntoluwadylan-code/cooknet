<?php 


declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cn_redirect('../index.php');
}

$token = $_POST['csrf_token'] ?? null;
if (!cn_csrf_verify(is_string($token) ? $token : null)) {
    cn_redirect('../index.php'); 
}

cn_logout_user();
cn_redirect('../index.php?logged_out=1');
