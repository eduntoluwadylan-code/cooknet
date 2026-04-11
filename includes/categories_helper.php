<?php

declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';

/**
 * Categories from `categories`, ordered by sort_order.
 *
 * @return array<int, array<string, mixed>>
 */
function cn_get_categories(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $st = cn_pdo()->query(
            'SELECT id, slug, label, icon, sort_order FROM categories ORDER BY sort_order ASC, id ASC'
        );
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $cache = is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        $cache = [];
    }
    return $cache;
}
