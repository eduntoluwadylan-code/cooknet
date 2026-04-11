<?php

declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/auth.php';

/**
 * Format minutes for display (e.g. 25 Mins, 1.5 Hrs).
 */
function cn_recipe_format_minutes(?int $minutes): string
{
    if ($minutes === null || $minutes < 1) {
        return '—';
    }
    if ($minutes < 60) {
        return $minutes . ' Mins';
    }
    $hours = $minutes / 60;
    if (abs($hours - round($hours)) < 0.01) {
        $h = (int) round($hours);

        return $h === 1 ? '1 Hr' : $h . ' Hrs';
    }

    return rtrim(rtrim(number_format($hours, 1), '0'), '.') . ' Hrs';
}

/**
 * @return array{
 *   recipe: array<string, mixed>,
 *   author: array<string, mixed>,
 *   hero_url: string,
 *   ingredients: list<array{quantity: string, name: string}>,
 *   steps: list<array{step_number: int, instruction: string}>
 * }|null
 */
function cn_recipe_detail_load(PDO $pdo, ?int $id, ?string $slug): ?array
{
    $slug = $slug !== null && $slug !== '' ? $slug : null;
    if ($id !== null && $id < 1) {
        $id = null;
    }
    if ($id === null && $slug === null) {
        return null;
    }

    if ($id !== null) {
        $st = $pdo->prepare(
            'SELECT r.*, c.slug AS category_slug, c.label AS category_label
             FROM recipes r
             INNER JOIN categories c ON c.id = r.category_id
             WHERE r.id = ?
             LIMIT 1'
        );
        $st->execute([$id]);
    } else {
        $st = $pdo->prepare(
            'SELECT r.*, c.slug AS category_slug, c.label AS category_label
             FROM recipes r
             INNER JOIN categories c ON c.id = r.category_id
             WHERE r.slug = ?
             LIMIT 1'
        );
        $st->execute([$slug]);
    }

    $recipe = $st->fetch(PDO::FETCH_ASSOC);
    if (!$recipe) {
        return null;
    }

    $viewerId = cn_user_id();
    $isOwner = $viewerId !== null && (int) $viewerId === (int) $recipe['user_id'];
    $isPublished = ($recipe['status'] ?? '') === 'published';
    if (!$isPublished && !$isOwner) {
        return null;
    }

    $recipeId = (int) $recipe['id'];

    $st = $pdo->prepare('SELECT display_name, avatar_url, role FROM users WHERE id = ? LIMIT 1');
    $st->execute([(int) $recipe['user_id']]);
    $author = $st->fetch(PDO::FETCH_ASSOC);
    if (!$author) {
        $author = ['display_name' => 'Unknown', 'avatar_url' => null, 'role' => 'user'];
    }

    $st = $pdo->prepare(
        'SELECT path_or_url FROM recipe_images WHERE recipe_id = ? AND role = \'hero\' ORDER BY sort_order ASC, id ASC LIMIT 1'
    );
    $st->execute([$recipeId]);
    $heroPath = $st->fetchColumn();
    $heroUrl = cn_recipe_resolve_asset_url($heroPath !== false ? (string) $heroPath : '');

    $st = $pdo->prepare(
        'SELECT quantity, name FROM recipe_ingredients WHERE recipe_id = ? ORDER BY sort_order ASC, id ASC'
    );
    $st->execute([$recipeId]);
    $ingredients = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $pdo->prepare(
        'SELECT step_number, instruction FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number ASC, id ASC'
    );
    $st->execute([$recipeId]);
    $steps = $st->fetchAll(PDO::FETCH_ASSOC);

    return [
        'recipe' => $recipe,
        'author' => $author,
        'hero_url' => $heroUrl,
        'ingredients' => $ingredients,
        'steps' => $steps,
    ];
}

function cn_recipe_resolve_asset_url(string $pathOrUrl): string
{
    $pathOrUrl = trim($pathOrUrl);
    if ($pathOrUrl === '') {
        return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=1600&q=80&auto=format&fit=crop';
    }
    if (preg_match('#^https?://#i', $pathOrUrl)) {
        return $pathOrUrl;
    }

    return $pathOrUrl;
}

function cn_recipe_author_label(string $role): string
{
    return match ($role) {
        'contributor' => 'Editorial Contributor',
        'admin' => 'CookNet Team',
        default => 'Community Chef',
    };
}

function cn_recipe_difficulty_label(?string $d): string
{
    if ($d === null || $d === '') {
        return '—';
    }

    return match ($d) {
        'easy' => 'Easy',
        'medium' => 'Medium',
        'hard' => 'Hard',
        default => ucfirst($d),
    };
}
