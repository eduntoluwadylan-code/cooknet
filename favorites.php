<?php
$cn_nav_active = '';
$cn_nav_search = false;
$cn_category_active = '';
$cn_submit_recipe_disabled = false;
$cn_sidebar_sticky_top = '96px';

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/category_recipes_query.php';

cn_require_login('index.php?login=1&next=' . rawurlencode('favorites.php'));

$pdo = cn_pdo();
$cn_user = cn_current_user($pdo);
if ($cn_user === null) {
    cn_logout_user();
    cn_redirect('index.php?login=1&next=' . rawurlencode('favorites.php'));
}

$cn_uid = (int) $cn_user['id'];

$sql = <<<'SQL'
SELECT r.slug, r.title, r.description, r.prep_minutes, r.cook_minutes, r.featured, r.rating_avg, r.rating_count,
  u.display_name AS author_name,
  (SELECT path_or_url FROM recipe_images ri WHERE ri.recipe_id = r.id AND ri.role = 'hero' ORDER BY ri.sort_order ASC, ri.id ASC LIMIT 1) AS hero_path
FROM user_favorites f
INNER JOIN recipes r ON r.id = f.recipe_id
INNER JOIN users u ON u.id = r.user_id
WHERE f.user_id = ?
ORDER BY f.created_at DESC
SQL;
$st = $pdo->prepare($sql);
$st->execute([$cn_uid]);
$cn_fav_recipes = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!doctype html>
<html lang="en">
  <head>
    <title>CookNet | Saved recipes</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link rel="stylesheet" href="assets/css/styles.css" />
  </head>
  <body>
    <?php require __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid" style="padding-top: 88px">
      <div class="row gx-4">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <main class="col-12 col-lg-9 col-xl-10">
          <section class="d-flex align-items-start gap-3 mb-4">
            <span class="material-symbols-outlined cn-fill d-none d-sm-inline" style="font-size: 2.5rem; color: var(--cn-primary)">favorite</span>
            <div>
              <h1 class="h2 fw-bold cn-headline mb-1" style="color: var(--cn-primary)">Saved recipes</h1>
              <p class="mb-0 cn-text-muted">Recipes you have added to your favorites.</p>
            </div>
          </section>

          <?php if (count($cn_fav_recipes) === 0) : ?>
            <section class="cn-card cn-card--lowest rounded-4 p-5 text-center mb-5">
              <p class="cn-text-muted mb-4">You have not saved any recipes yet. Browse the home page and tap “Save to Favorites” on a dish you love.</p>
              <a class="btn cn-btn-primary cn-editorial-shadow px-4 py-3" href="index.php">Browse recipes</a>
            </section>
          <?php else : ?>
            <section class="row g-4 mb-5">
              <?php foreach ($cn_fav_recipes as $cn_r) : ?>
                <?php
                $cn_href = 'recipe-details.php?slug=' . rawurlencode((string) $cn_r['slug']);
                $cn_img = cn_recipe_resolve_asset_url(isset($cn_r['hero_path']) ? (string) $cn_r['hero_path'] : '');
                $cn_title = (string) $cn_r['title'];
                $cn_desc = isset($cn_r['description']) ? trim((string) $cn_r['description']) : '';
                $cn_desc_short = $cn_desc !== '' ? (mb_strlen($cn_desc) > 160 ? mb_substr($cn_desc, 0, 157) . '…' : $cn_desc) : '';
                $cn_mins = cn_recipe_card_minutes_total($cn_r);
                $cn_time_label = $cn_mins !== null ? strtoupper((string) preg_replace('/\s+/', ' ', cn_recipe_format_minutes($cn_mins))) : null;
                $cn_feat = !empty($cn_r['featured']) && (int) $cn_r['featured'] === 1;
                $cn_rating = isset($cn_r['rating_avg']) ? (float) $cn_r['rating_avg'] : 0.0;
                $cn_rcount = isset($cn_r['rating_count']) ? (int) $cn_r['rating_count'] : 0;
                $cn_author = isset($cn_r['author_name']) ? (string) $cn_r['author_name'] : '';
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                  <a class="text-decoration-none text-reset" href="<?php echo htmlspecialchars($cn_href, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="ratio ratio-4x3 rounded-4 overflow-hidden mb-3 position-relative">
                      <img src="<?php echo htmlspecialchars($cn_img, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="w-100 h-100 object-fit-cover" />
                      <?php if ($cn_feat) : ?>
                        <div class="position-absolute top-0 start-0 p-3">
                          <span class="cn-chip" style="background: var(--cn-secondary-container); color: #663100">Chef's choice</span>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="px-1">
                      <div class="d-flex justify-content-between align-items-start gap-2">
                        <h2 class="h5 fw-bold cn-headline mb-1"><?php echo htmlspecialchars($cn_title, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <span class="material-symbols-outlined cn-fill cn-text-muted" style="color: var(--cn-primary)" aria-hidden="true">favorite</span>
                      </div>
                      <?php if ($cn_author !== '') : ?>
                        <p class="small cn-text-muted mb-2">By <?php echo htmlspecialchars($cn_author, ENT_QUOTES, 'UTF-8'); ?></p>
                      <?php endif; ?>
                      <?php if ($cn_desc_short !== '') : ?>
                        <p class="cn-text-muted mb-2 small"><?php echo htmlspecialchars($cn_desc_short, ENT_QUOTES, 'UTF-8'); ?></p>
                      <?php endif; ?>
                      <div class="d-flex flex-wrap gap-3 small fw-bold" style="color: var(--cn-primary)">
                        <?php if ($cn_time_label !== null) : ?>
                          <span><span class="material-symbols-outlined align-middle" style="font-size: 18px">timer</span> <?php echo htmlspecialchars($cn_time_label, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <span><span class="material-symbols-outlined cn-fill align-middle" style="font-size: 18px">star</span> <?php echo htmlspecialchars(number_format($cn_rating, 1), ENT_QUOTES, 'UTF-8'); ?> (<?php echo (string) $cn_rcount; ?>)</span>
                      </div>
                    </div>
                  </a>
                </div>
              <?php endforeach; ?>
            </section>
          <?php endif; ?>

          <div class="cn-mobile-nav-spacer d-md-none"></div>
        </main>
      </div>
    </div>

    <div class="d-md-none fixed-bottom bg-white shadow-lg">
      <div class="d-flex justify-content-around py-2">
        <a class="text-decoration-none text-center cn-text-muted" href="index.php" data-nav style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">home</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Home</div>
        </a>
        <a class="text-decoration-none text-center cn-text-muted" href="index.php" style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">explore</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Browse</div>
        </a>
        <a class="text-decoration-none text-center" href="favorites.php" data-nav style="color: var(--cn-primary)">
          <div><span class="material-symbols-outlined cn-fill">bookmark</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Saved</div>
        </a>
        <a class="text-decoration-none text-center cn-text-muted" href="dashboard.php" data-nav style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">person</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Profile</div>
        </a>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="assets/js/app.js"></script>
  </body>
</html>
