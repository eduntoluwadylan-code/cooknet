<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['recipes' => []]);
    exit;
}

require_once __DIR__ . '/includes/db_conn.php';
require_once __DIR__ . '/services/SearchService.php';

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$pdo = cn_pdo();
$svc = new SearchService();
echo json_encode(['recipes' => $svc->suggest($pdo, $q, 12)], JSON_UNESCAPED_UNICODE);
