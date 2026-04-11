<?php

declare(strict_types=1);

/**
 * Home page: spotlight recipe and published recipe lists.
 */
final class HomeFeedService
{
    /**
     * Hero / recipe of the day: latest updated among featured=1, else latest published overall.
     *
     * @return array<string, mixed>|null
     */
    public function spotlightRecipe(PDO $pdo): ?array
    {
        $sql = 'SELECT
                  r.id,
                  r.slug,
                  r.title,
                  r.prep_minutes,
                  r.cook_minutes,
                  u.display_name AS author_name,
                  (
                    SELECT ri.path_or_url
                    FROM recipe_images ri
                    WHERE ri.recipe_id = r.id AND ri.role = \'hero\'
                    ORDER BY ri.sort_order ASC, ri.id ASC
                    LIMIT 1
                  ) AS hero_path
                FROM recipes r
                INNER JOIN users u ON u.id = r.user_id
                WHERE r.status = \'published\'
                ORDER BY r.featured DESC, r.updated_at DESC, r.id DESC
                LIMIT 1';
        $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Published recipes for the grid.
     *
     * @param 'all'|'favorites' $sort
     * @return list<array<string, mixed>>
     */
    public function listRecipes(PDO $pdo, string $sort, int $limit = 24): array
    {
        if ($limit < 1) {
            $limit = 24;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $sort = $sort === 'favorites' ? 'favorites' : 'all';

        $orderBy = $sort === 'favorites'
            ? '(SELECT COUNT(*) FROM user_favorites f WHERE f.recipe_id = r.id) DESC, r.updated_at DESC, r.id DESC'
            : 'r.updated_at DESC, r.id DESC';

        $sql = 'SELECT
                  r.id,
                  r.slug,
                  r.title,
                  r.description,
                  r.prep_minutes,
                  r.cook_minutes,
                  r.featured,
                  r.rating_avg,
                  r.rating_count,
                  c.label AS category_label,
                  (
                    SELECT ri.path_or_url
                    FROM recipe_images ri
                    WHERE ri.recipe_id = r.id AND ri.role = \'hero\'
                    ORDER BY ri.sort_order ASC, ri.id ASC
                    LIMIT 1
                  ) AS hero_path
                FROM recipes r
                INNER JOIN categories c ON c.id = r.category_id
                WHERE r.status = \'published\'
                ORDER BY ' . $orderBy . '
                LIMIT ' . (int) $limit;

        $st = $pdo->query($sql);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }
}
