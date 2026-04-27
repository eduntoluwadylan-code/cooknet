<?php

declare(strict_types=1);

/** Published recipe title/description search for navbar suggest. */
final class SearchService 
{
    /**
     * @return list<array{slug: string, title: string, category_label: string}>
     */
    public function suggest(PDO $pdo, string $q, int $limit = 12): array
    {
        $q = trim($q);
        $q = str_replace(['%', '_'], '', $q);
        if (mb_strlen($q) < 2) {
            return [];
        }
        $lim = max(1, min(30, $limit));
        $pat = '%' . $q . '%';

        $sql = 'SELECT r.slug, r.title, c.label AS category_label
                FROM recipes r
                INNER JOIN categories c ON c.id = r.category_id
                WHERE r.status = \'published\'
                  AND (r.title LIKE ? OR IFNULL(r.description, \'\') LIKE ?)
                ORDER BY r.updated_at DESC
                LIMIT ' . $lim;
        $st = $pdo->prepare($sql);
        $st->execute([$pat, $pat]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }
}
