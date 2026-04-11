<?php

declare(strict_types=1);

/**
 * Persists a recipe with ingredients, steps, and optional hero image.
 */
class CreateRecipeService
{
    private string $projectRoot;

    public function __construct(?string $projectRoot = null)
    {
        $this->projectRoot = $projectRoot ?? dirname(__DIR__);
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @return int New recipe id
     */
    public function create(PDO $pdo, int $userId, array $post, array $files): int
    {
        $title = trim((string) ($post['title'] ?? ''));
        if ($title === '' || strlen($title) > 255) {
            throw new InvalidArgumentException('Please enter a recipe title.');
        }

        $description = trim((string) ($post['description'] ?? ''));
        $categoryId = isset($post['category_id']) ? (int) $post['category_id'] : 0;
        if ($categoryId < 1) {
            throw new InvalidArgumentException('Please select a category.');
        }

        $st = $pdo->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
        $st->execute([$categoryId]);
        if (!$st->fetch()) {
            throw new InvalidArgumentException('Invalid category.');
        }

        $status = (string) ($post['recipe_action'] ?? 'draft');
        if ($status !== 'published' && $status !== 'draft') {
            $status = 'draft';
        }

        $prepMinutes = $this->prepMinutesFromRange((string) ($post['prep_range'] ?? ''));
        $cookMinutes = $this->optionalUInt($post['cook_minutes'] ?? null);
        $difficulty = $this->normalizeDifficulty($post['difficulty'] ?? null);

        $ingredients = $this->parseIngredients($post);
        $steps = $this->parseSteps($post);

        if ($status === 'published') {
            if (count($ingredients) < 1) {
                throw new InvalidArgumentException('Add at least one ingredient to publish.');
            }
            if (count($steps) < 1) {
                throw new InvalidArgumentException('Add at least one instruction step to publish.');
            }
            $hero = $files['hero_image'] ?? null;
            if (!is_array($hero) || ($hero['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                throw new InvalidArgumentException('A hero image is required to publish.');
            }
        }

        $slug = $this->ensureUniqueSlug($pdo, $this->slugify($title));

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                'INSERT INTO recipes (user_id, category_id, title, slug, description, status, prep_minutes, cook_minutes, difficulty, featured, rating_avg, rating_count)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0.00, 0)'
            );
            $ins->execute([
                $userId,
                $categoryId,
                $title,
                $slug,
                $description !== '' ? $description : null,
                $status,
                $prepMinutes,
                $cookMinutes,
                $difficulty,
            ]);
            $recipeId = (int) $pdo->lastInsertId();

            $ingStmt = $pdo->prepare(
                'INSERT INTO recipe_ingredients (recipe_id, sort_order, quantity, name) VALUES (?, ?, ?, ?)'
            );
            foreach ($ingredients as $i => $row) {
                $ingStmt->execute([$recipeId, $i, $row['quantity'], $row['name']]);
            }

            $stepStmt = $pdo->prepare(
                'INSERT INTO recipe_steps (recipe_id, step_number, instruction) VALUES (?, ?, ?)'
            );
            foreach ($steps as $i => $text) {
                $stepStmt->execute([$recipeId, $i + 1, $text]);
            }

            $heroFile = $files['hero_image'] ?? null;
            if (is_array($heroFile) && ($heroFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $relativePath = $this->storeHeroImage($recipeId, $heroFile);
                $imgStmt = $pdo->prepare(
                    'INSERT INTO recipe_images (recipe_id, role, path_or_url, alt_text, sort_order) VALUES (?, \'hero\', ?, ?, 0)'
                );
                $imgStmt->execute([$recipeId, $relativePath, $title]);
            }

            $pdo->commit();
            return $recipeId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $post
     * @return list<array{quantity: string, name: string}>
     */
    protected function parseIngredients(array $post): array
    {
        $qty = $post['ingredient_qty'] ?? [];
        $name = $post['ingredient_name'] ?? [];
        if (!is_array($qty)) {
            $qty = [];
        }
        if (!is_array($name)) {
            $name = [];
        }
        $out = [];
        $n = max(count($qty), count($name));
        for ($i = 0; $i < $n; $i++) {
            $q = isset($qty[$i]) ? trim((string) $qty[$i]) : '';
            $nm = isset($name[$i]) ? trim((string) $name[$i]) : '';
            if ($nm === '') {
                continue;
            }
            $out[] = ['quantity' => $q, 'name' => $nm];
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $post
     * @return list<string>
     */
    protected function parseSteps(array $post): array
    {
        $raw = $post['step_instructions'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $line) {
            $t = trim((string) $line);
            if ($t !== '') {
                $out[] = $t;
            }
        }
        return $out;
    }

    protected function prepMinutesFromRange(string $range): ?int
    {
        return match ($range) {
            '15_30' => 25,
            '30_60' => 45,
            '60_120' => 90,
            '120_plus' => 150,
            default => null,
        };
    }

    protected function optionalUInt(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        $n = (int) $v;
        return $n > 0 ? $n : null;
    }

    protected function normalizeDifficulty(mixed $v): ?string
    {
        $s = is_string($v) ? $v : '';
        return match ($s) {
            'easy', 'medium', 'hard' => $s,
            default => null,
        };
    }

    protected function slugify(string $title): string
    {
        $s = strtolower(trim($title));
        $s = preg_replace('/[^a-z0-9]+/i', '-', $s) ?? '';
        $s = trim($s, '-');
        if ($s === '') {
            $s = 'recipe';
        }
        return substr($s, 0, 200);
    }

    protected function ensureUniqueSlug(PDO $pdo, string $base): string
    {
        $slug = $base;
        $n = 1;
        $st = $pdo->prepare('SELECT COUNT(*) FROM recipes WHERE slug = ?');
        while (true) {
            $st->execute([$slug]);
            if ((int) $st->fetchColumn() === 0) {
                return $slug;
            }
            $suffix = '-' . $n++;
            $slug = substr($base, 0, 255 - strlen($suffix)) . $suffix;
        }
    }

    /** Unique slug when updating an existing recipe (excludes $exceptId from collision checks). */
    protected function ensureUniqueSlugExcluding(PDO $pdo, string $base, int $exceptId): string
    {
        $slug = $base;
        $n = 1;
        $st = $pdo->prepare('SELECT COUNT(*) FROM recipes WHERE slug = ? AND id <> ?');
        while (true) {
            $st->execute([$slug, $exceptId]);
            if ((int) $st->fetchColumn() === 0) {
                return $slug;
            }
            $suffix = '-' . $n++;
            $slug = substr($base, 0, 255 - strlen($suffix)) . $suffix;
        }
    }

    /**
     * @param array{tmp_name: string, error: int, size: int, name?: string} $file
     */
    protected function storeHeroImage(int $recipeId, array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Image upload failed.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Image must be 5MB or smaller.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Invalid upload.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!is_string($mime) || !isset($map[$mime])) {
            throw new InvalidArgumentException('Use a JPG, PNG, or WebP image.');
        }
        $ext = $map[$mime];
        $dir = $this->projectRoot . '/uploads/recipes/' . $recipeId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create upload directory.');
        }

        $relative = 'uploads/recipes/' . $recipeId . '/hero.' . $ext;
        $absolute = $this->projectRoot . '/' . $relative;
        if (!move_uploaded_file($tmp, $absolute)) {
            throw new RuntimeException('Could not save the image.');
        }

        return $relative;
    }
}
