<?php
/**
 * Shared defaults for CookNet partials.
 * Override these in each page before including navbar/sidebar.
 */
if (!isset($cn_nav_active)) {
    $cn_nav_active = 'browse'; // browse | seasonal | community
}
if (!isset($cn_nav_search)) {
    $cn_nav_search = false;
}
if (!isset($cn_category_active)) {
    $cn_category_active = null; // main_dishes | breakfast | vegetarian | desserts | quick_meals
}
if (!isset($cn_submit_recipe_disabled)) {
    $cn_submit_recipe_disabled = false;
}
if (!isset($cn_sidebar_sticky_top)) {
    $cn_sidebar_sticky_top = '96px';
}

/**
 * When true, failed recipe saves show the real exception message (development).
 * Create an empty file `includes/production.mode` to hide details on public hosting.
 */
if (!defined('CN_DEBUG')) {
    define('CN_DEBUG', !file_exists(__DIR__ . '/production.mode'));
}
