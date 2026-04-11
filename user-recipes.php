<?php
$cn_nav_active = '';
$cn_nav_search = false;
$cn_category_active = null;
$cn_submit_recipe_disabled = false;
$cn_sidebar_sticky_top = '96px';

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/recipe_detail_query.php';

cn_require_login('index.php?login=1&next=' . rawurlencode('user-recipes.php'));

$pdo = cn_pdo();
$cn_user = cn_current_user($pdo);
if ($cn_user === null) {
    cn_logout_user();
    cn_redirect('index.php?login=1&next=' . rawurlencode('user-recipes.php'));
}

$cn_uid = (int) $cn_user['id'];

$sql = <<<'SQL'
SELECT r.id, r.title, r.slug, r.status, r.updated_at, r.prep_minutes, r.rating_avg, r.rating_count,
  c.label AS category_label,
  (SELECT path_or_url FROM recipe_images ri WHERE ri.recipe_id = r.id AND ri.role = 'hero' ORDER BY ri.sort_order ASC, ri.id ASC LIMIT 1) AS hero_image
FROM recipes r
INNER JOIN categories c ON c.id = r.category_id
WHERE r.user_id = ?
ORDER BY r.updated_at DESC
SQL;
$st = $pdo->prepare($sql);
$st->execute([$cn_uid]);
$cn_my_recipes = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

function cn_user_recipes_time_ago(?string $mysqlDate): string
{
    if ($mysqlDate === null || $mysqlDate === '') {
        return '';
    }
    $t = strtotime($mysqlDate);
    if ($t === false) {
        return '';
    }
    $diff = time() - $t;
    if ($diff < 120) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return (int) ($diff / 60) . ' min ago';
    }
    if ($diff < 86400) {
        return (int) ($diff / 3600) . ' hours ago';
    }
    if ($diff < 604800) {
        return (int) ($diff / 86400) . ' days ago';
    }

    return date('M j, Y', $t);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <title>CookNet | My recipes</title>
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
            <span class="material-symbols-outlined cn-fill d-none d-sm-inline" style="font-size: 2.5rem; color: var(--cn-primary)">restaurant_menu</span>
            <div>
              <h1 class="h2 fw-bold cn-headline mb-1" style="color: var(--cn-primary)">My recipes</h1>
              <p class="mb-0 cn-text-muted">Everything you have posted on CookNet, including drafts.</p>
            </div>
          </section>

          <?php if (count($cn_my_recipes) === 0) : ?>
            <section class="cn-card cn-card--lowest rounded-4 p-5 text-center mb-5">
              <p class="cn-text-muted mb-4">You have not created any recipes yet. Start with a title and a few ingredients — you can save as a draft until you are ready to publish.</p>
              <a class="btn cn-btn-primary cn-editorial-shadow px-4 py-3" href="create-recipe.php">Create recipe</a>
            </section>
          <?php else : ?>
            <section class="row g-4 mb-5">
              <?php foreach ($cn_my_recipes as $cn_r) : ?>
                <?php
                $cn_id = (int) $cn_r['id'];
                $cn_detail_href = 'recipe-details.php?id=' . $cn_id;
                $cn_img = cn_recipe_resolve_asset_url(isset($cn_r['hero_image']) ? (string) $cn_r['hero_image'] : '');
                $cn_rating = isset($cn_r['rating_avg']) ? (float) $cn_r['rating_avg'] : 0.0;
                $cn_rcount = (int) ($cn_r['rating_count'] ?? 0);
                $cn_show_rating = $cn_rcount > 0 && $cn_rating > 0;
                $cn_prep = isset($cn_r['prep_minutes']) && $cn_r['prep_minutes'] !== null ? (int) $cn_r['prep_minutes'] : null;
                ?>
                <div class="col-12 col-md-6">
                  <div class="cn-card rounded-4 overflow-hidden cn-editorial-shadow h-100">
                    <a class="text-reset text-decoration-none" href="<?php echo htmlspecialchars($cn_detail_href, ENT_QUOTES, 'UTF-8'); ?>">
                      <div class="position-relative" style="height: 190px">
                        <img
                          alt=""
                          src="<?php echo htmlspecialchars($cn_img, ENT_QUOTES, 'UTF-8'); ?>"
                          class="w-100 h-100 object-fit-cover"
                        />
                        <?php if ($cn_show_rating) : ?>
                          <div class="position-absolute top-0 end-0 p-3">
                            <div class="rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1 cn-editorial-shadow" style="background: rgba(255,255,255,0.88)">
                              <span class="material-symbols-outlined cn-fill" style="font-size: 16px; color: var(--cn-secondary-container)">star</span>
                              <span class="fw-bold small" style="color: var(--cn-primary)"><?php echo htmlspecialchars(number_format($cn_rating, 1), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                          </div>
                        <?php endif; ?>
                      </div>
                    </a>
                    <div class="p-4">
                      <div class="d-flex gap-2 mb-2 flex-wrap">
                        <span class="cn-chip" style="background: #ffdcc5; color: #301400"><?php echo htmlspecialchars((string) $cn_r['category_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($cn_prep !== null) : ?>
                          <span class="cn-chip" style="background: var(--cn-surface-container-highest); color: var(--cn-on-surface-variant)"><?php echo (int) $cn_prep; ?> MIN</span>
                        <?php endif; ?>
                        <?php if (($cn_r['status'] ?? '') === 'draft') : ?>
                          <span class="cn-chip cn-chip--tertiary">Draft</span>
                        <?php endif; ?>
                      </div>
                      <div class="h5 fw-bold cn-headline mb-3" style="color: var(--cn-primary)"><?php echo htmlspecialchars((string) $cn_r['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="small cn-text-muted fst-italic">Updated <?php echo htmlspecialchars(cn_user_recipes_time_ago(isset($cn_r['updated_at']) ? (string) $cn_r['updated_at'] : null), ENT_QUOTES, 'UTF-8'); ?></div>
                        <a class="btn rounded-circle p-2" style="background: rgba(161, 212, 148, 0.18); color: var(--cn-primary)" aria-label="View recipe" href="<?php echo htmlspecialchars($cn_detail_href, ENT_QUOTES, 'UTF-8'); ?>">
                          <span class="material-symbols-outlined">visibility</span>
                        </a>
                      </div>
                    </div>
                  </div>
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
        <a class="text-decoration-none text-center cn-text-muted" href="favorites.php" data-nav style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">bookmark</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Saved</div>
        </a>
        <a class="text-decoration-none text-center" href="dashboard.php" data-nav style="color: var(--cn-primary)">
          <div><span class="material-symbols-outlined cn-fill">person</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Profile</div>
        </a>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="assets/js/app.js"></script>
  </body>
</html>
