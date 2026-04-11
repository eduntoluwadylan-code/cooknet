<?php
/**
 * Renders category navigation links (shared by desktop sidebar + mobile nav).
 * Expects $cn_category_active and helpers from config.php.
 * Loads rows from the `categories` table.
 */
require_once __DIR__ . '/categories_helper.php';

$cn_cats = cn_get_categories();

foreach ($cn_cats as $def) {
    $key = (string) $def['slug'];
    $icon = (string) ($def['icon'] ?? 'label');
    $label = (string) ($def['label'] ?? $key);
    $cid = isset($def['id']) ? (int) $def['id'] : 0;
    $href = $cid > 0 ? 'category.php?id=' . $cid : '#';
    $isActive = ($cn_category_active === $key);
    if ($isActive) {
        $itemClass = 'list-group-item list-group-item-action cn-card cn-card--lowest mb-2';
        $inner = '<span class="material-symbols-outlined me-2 align-middle">' . htmlspecialchars($icon) . '</span><span class="fw-semibold">' . htmlspecialchars($label) . '</span>';
    } else {
        $itemClass = 'list-group-item list-group-item-action cn-no-border bg-transparent cn-text-muted mb-1';
        $inner = '<span class="material-symbols-outlined me-2 align-middle">' . htmlspecialchars($icon) . '</span> ' . htmlspecialchars($label);
    }
    echo '<a class="' . htmlspecialchars($itemClass) . '" href="' . htmlspecialchars($href) . '">' . $inner . '</a>' . "\n";
}
