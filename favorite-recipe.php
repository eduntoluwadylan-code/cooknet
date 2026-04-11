<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/services/FavoriteRecipeService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cn_redirect('index.php');
}

$token = $_POST['csrf_token'] ?? null;
if (!cn_csrf_verify(is_string($token) ? $token : null)) {
    cn_flash_set('favorite_error', 'Invalid session. Please try again.');
    cn_redirect('index.php');
}

$recipeId = isset($_POST['recipe_id']) ? (int) $_POST['recipe_id'] : 0;
$nextRaw = isset($_POST['next']) ? (string) $_POST['next'] : 'index.php';
$next = cn_safe_redirect_target($nextRaw, 'index.php');

$uid = cn_user_id();
if ($uid === null) {
    cn_redirect('index.php?login=1&next=' . rawurlencode($next));
}

$pdo = cn_pdo();
$service = new FavoriteRecipeService();
$action = isset($_POST['action']) ? trim((string) $_POST['action']) : 'add';
if ($action !== 'remove') {
    $action = 'add';
}

try {
    if ($action === 'remove') {
        $service->remove($pdo, $uid, $recipeId);
        cn_flash_set('favorite_ok', 'Removed from your favorites.');
    } else {
        $service->add($pdo, $uid, $recipeId);
        cn_flash_set('favorite_ok', 'Saved to your favorites.');
    }
} catch (InvalidArgumentException $e) {
    cn_flash_set('favorite_error', $e->getMessage());
} catch (Throwable $e) {
    cn_flash_set('favorite_error', CN_DEBUG ? $e->getMessage() : 'Something went wrong. Please try again.');
}

cn_redirect($next);
