<?php

declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/recipe_detail_query.php';

/**
 * Load one category by primary key.
 *
 * @return array{id: int, slug: string, label: string, icon: string}|null
 */
function cn_category_by_id(PDO $pdo, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $st = $pdo->prepare('SELECT id, slug, label, icon FROM categories WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'slug' => (string) $row['slug'],
        'label' => (string) $row['label'],
        'icon' => (string) ($row['icon'] ?? 'label'),
    ];
}

/**
 * Published recipes in a category, newest / featured first.
 *
 * @return list<array<string, mixed>>
 */
function cn_recipes_for_category(PDO $pdo, int $categoryId): array
{
    if ($categoryId < 1) {
        return [];
    }
    $sql = 'SELECT
              r.id,
              r.slug,
              r.title,
              r.description,
              r.prep_minutes,
              r.cook_minutes,
              r.difficulty,
              r.featured,
              r.rating_avg,
              r.rating_count,
              (
                SELECT ri.path_or_url
                FROM recipe_images ri
                WHERE ri.recipe_id = r.id AND ri.role = \'hero\'
                ORDER BY ri.sort_order ASC, ri.id ASC
                LIMIT 1
              ) AS hero_path
            FROM recipes r
            WHERE r.category_id = ? AND r.status = \'published\'
            ORDER BY r.featured DESC, r.updated_at DESC, r.id DESC';
    $st = $pdo->prepare($sql);
    $st->execute([$categoryId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    return is_array($rows) ? $rows : [];
}

/**
 * Total time label for cards (prep + cook), or null if unknown.
 */
function cn_recipe_card_minutes_total(?array $r): ?int
{
    if ($r === null) {
        return null;
    }
    $p = isset($r['prep_minutes']) && $r['prep_minutes'] !== null && $r['prep_minutes'] !== '' ? (int) $r['prep_minutes'] : 0;
    $c = isset($r['cook_minutes']) && $r['cook_minutes'] !== null && $r['cook_minutes'] !== '' ? (int) $r['cook_minutes'] : 0;
    $t = $p + $c;

    return $t > 0 ? $t : null;
}
