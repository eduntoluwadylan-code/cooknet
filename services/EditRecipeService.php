<?php
declare(strict_types=1);

require_once __DIR__ . '/CreateRecipeService.php';

/** Updates an existing recipe (owner only). */
final class EditRecipeService extends CreateRecipeService
{
    public function update(PDO $pdo, int $userId, int $recipeId, array $post, array $files): void
    {
        $st = $pdo->prepare('SELECT id, user_id, slug, title FROM recipes WHERE id = ? LIMIT 1');
        $st->execute([$recipeId]);
        $recipe = $st->fetch(PDO::FETCH_ASSOC);
        if (!$recipe || (int) $recipe['user_id'] !== $userId) {
            throw new InvalidArgumentException('Recipe not found or you do not have access.');
        }

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

        $st = $pdo->prepare('SELECT 1 FROM recipe_images WHERE recipe_id = ? AND role = \'hero\' LIMIT 1');
        $st->execute([$recipeId]);
        $hasHero = (bool) $st->fetchColumn();

        $heroFile = $files['hero_image'] ?? null;
        $newHero = is_array($heroFile) && ($heroFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

        if ($status === 'published') {
            if (count($ingredients) < 1) {
                throw new InvalidArgumentException('Add at least one ingredient to publish.');
            }
            if (count($steps) < 1) {
                throw new InvalidArgumentException('Add at least one instruction step to publish.');
            }
            if (!$hasHero && !$newHero) {
                throw new InvalidArgumentException('A hero image is required to publish.');
            }
        }

        $candidate = $this->slugify($title);
        $slug = $this->ensureUniqueSlugExcluding($pdo, $candidate, $recipeId);

        $pdo->beginTransaction();
        try {
            $upd = $pdo->prepare(
                'UPDATE recipes SET category_id = ?, title = ?, slug = ?, description = ?, status = ?, prep_minutes = ?, cook_minutes = ?, difficulty = ? WHERE id = ? AND user_id = ?'
            );
            $upd->execute([
                $categoryId,
                $title,
                $slug,
                $description !== '' ? $description : null,
                $status,
                $prepMinutes,
                $cookMinutes,
                $difficulty,
                $recipeId,
                $userId,
            ]);
            if ($upd->rowCount() === 0) {
                throw new InvalidArgumentException('Could not update recipe.');
            }

            $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = ?')->execute([$recipeId]);
            $pdo->prepare('DELETE FROM recipe_steps WHERE recipe_id = ?')->execute([$recipeId]);

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

            if ($newHero) {
                $relativePath = $this->storeHeroImage($recipeId, $heroFile);
                $st = $pdo->prepare('SELECT id FROM recipe_images WHERE recipe_id = ? AND role = \'hero\' LIMIT 1');
                $st->execute([$recipeId]);
                if ($st->fetch()) {
                    $pdo->prepare(
                        'UPDATE recipe_images SET path_or_url = ?, alt_text = ? WHERE recipe_id = ? AND role = \'hero\''
                    )->execute([$relativePath, $title, $recipeId]);
                } else {
                    $pdo->prepare(
                        'INSERT INTO recipe_images (recipe_id, role, path_or_url, alt_text, sort_order) VALUES (?, \'hero\', ?, ?, 0)'
                    )->execute([$recipeId, $relativePath, $title]);
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
