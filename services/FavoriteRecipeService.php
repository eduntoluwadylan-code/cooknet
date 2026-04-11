<?php

declare(strict_types=1);

/**
 * Add or remove rows in user_favorites.
 */
final class FavoriteRecipeService
{
    /**
     * @throws InvalidArgumentException When the recipe cannot be favorited.
     */
    public function add(PDO $pdo, int $userId, int $recipeId): void
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('You must be signed in to save recipes.');
        }
        if ($recipeId < 1) {
            throw new InvalidArgumentException('Invalid recipe.');
        }

        $st = $pdo->prepare('SELECT user_id, status FROM recipes WHERE id = ? LIMIT 1');
        $st->execute([$recipeId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new InvalidArgumentException('Recipe not found.');
        }

        if ((int) $row['user_id'] === $userId) {
            throw new InvalidArgumentException('You cannot save your own recipe.');
        }

        if (($row['status'] ?? '') !== 'published') {
            throw new InvalidArgumentException('This recipe cannot be saved.');
        }

        $ins = $pdo->prepare('INSERT IGNORE INTO user_favorites (user_id, recipe_id) VALUES (?, ?)');
        $ins->execute([$userId, $recipeId]);
    }

    /**
     * Remove a recipe from the user's saved list.
     *
     * @throws InvalidArgumentException When inputs are invalid.
     */
    public function remove(PDO $pdo, int $userId, int $recipeId): void
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('You must be signed in.');
        }
        if ($recipeId < 1) {
            throw new InvalidArgumentException('Invalid recipe.');
        }

        $del = $pdo->prepare('DELETE FROM user_favorites WHERE user_id = ? AND recipe_id = ?');
        $del->execute([$userId, $recipeId]);
    }
}
