<?php
$cn_nav_active = 'community';
$cn_nav_search = false;
$cn_category_active = null;
$cn_submit_recipe_disabled = false;
$cn_sidebar_sticky_top = '96px';
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

cn_require_login('index.php?login=1');

$pdo = cn_pdo();
$cn_dashboard_user = cn_current_user($pdo);
if ($cn_dashboard_user === null) {
    cn_logout_user();
    cn_redirect('index.php?login=1');
}

$cn_uid = (int) $cn_dashboard_user['id'];

$st = $pdo->prepare('SELECT COUNT(*) FROM recipes WHERE user_id = ? AND status = \'published\'');
$st->execute([$cn_uid]);
$cn_stat_recipes = (int) $st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM user_favorites WHERE user_id = ?');
$st->execute([$cn_uid]);
$cn_stat_saved = (int) $st->fetchColumn();

$cn_stat_followers = 0;

$sqlRecipes = <<<'SQL'
SELECT r.id, r.title, r.slug, r.status, r.updated_at, r.prep_minutes, r.rating_avg, r.rating_count,
  c.label AS category_label,
  (SELECT path_or_url FROM recipe_images ri WHERE ri.recipe_id = r.id AND ri.role = 'hero' ORDER BY ri.sort_order ASC, ri.id ASC LIMIT 1) AS hero_image
FROM recipes r
JOIN categories c ON c.id = r.category_id
WHERE r.user_id = ?
ORDER BY r.updated_at DESC
LIMIT 6
SQL;
$st = $pdo->prepare($sqlRecipes);
$st->execute([$cn_uid]);
$cn_my_recipes = $st->fetchAll();

$sqlFav = <<<'SQL'
SELECT r.id, r.title, r.slug, u.display_name AS author_name,
  (SELECT path_or_url FROM recipe_images ri WHERE ri.recipe_id = r.id AND ri.role = 'hero' ORDER BY ri.sort_order ASC, ri.id ASC LIMIT 1) AS hero_image
FROM user_favorites f
JOIN recipes r ON r.id = f.recipe_id
JOIN users u ON u.id = r.user_id
WHERE f.user_id = ?
ORDER BY f.created_at DESC
LIMIT 10
SQL;
$st = $pdo->prepare($sqlFav);
$st->execute([$cn_uid]);
$cn_saved_rows = $st->fetchAll();

$cn_placeholder_food = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&q=80&auto=format&fit=crop';

function cn_dashboard_time_ago(?string $mysqlDate): string
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
    <title>CookNet | User Dashboard</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    />
    <link rel="stylesheet" href="assets/css/styles.css" />
  </head>

  <body>
    <?php require __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid" style="padding-top: 88px">
      <div class="row gx-4">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <main class="col-12 col-lg-9 col-xl-10">
          <!-- Header -->
          <header class="mb-4 mb-md-5 position-relative">
            <div class="row g-4 align-items-end">
              <div class="col-12 col-md-8 d-flex flex-column flex-sm-row gap-4 align-items-start align-items-sm-end">
                <div class="position-relative">
                  <div
                    class="rounded-4 overflow-hidden cn-editorial-shadow"
                    style="width: 112px; height: 112px; transform: rotate(3deg)"
                  >
                    <?php
                    $cn_dash_av = !empty($cn_dashboard_user['avatar_url'])
                        ? (string) $cn_dashboard_user['avatar_url']
                        : 'https://ui-avatars.com/api/?name=' . rawurlencode((string) $cn_dashboard_user['display_name']) . '&background=154212&color=fff&size=224';
                    ?>
                    <img
                      src="<?php echo htmlspecialchars($cn_dash_av, ENT_QUOTES, 'UTF-8'); ?>"
                      alt=""
                      class="w-100 h-100 object-fit-cover"
                    />
                  </div>
                  <?php if (($cn_dashboard_user['role'] ?? '') === 'contributor' || ($cn_dashboard_user['role'] ?? '') === 'admin') : ?>
                  <div class="position-absolute bottom-0 end-0 translate-middle-y p-2 rounded-4 cn-editorial-shadow" style="background: var(--cn-secondary-container); color: #fff">
                    <span class="material-symbols-outlined cn-fill" style="font-size: 18px">verified</span>
                  </div>
                  <?php endif; ?>
                </div>

                <div class="flex-grow-1">
                  <h1 class="display-6 fw-bold cn-headline mb-1" style="color: var(--cn-primary)">Welcome back, <?php echo htmlspecialchars((string) $cn_dashboard_user['display_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                  <p class="cn-text-muted mb-3"><?php echo !empty($cn_dashboard_user['bio']) ? htmlspecialchars((string) $cn_dashboard_user['bio'], ENT_QUOTES, 'UTF-8') : 'Your CookNet dashboard — create, save, and curate recipes.'; ?></p>

                  <div class="d-flex flex-wrap gap-4 gap-md-5">
                    <div class="flex-shrink-0">
                      <div class="h3 fw-bold cn-headline mb-0" style="color: var(--cn-primary)"><?php echo number_format($cn_stat_recipes); ?></div>
                      <div class="small text-uppercase fw-bold cn-text-muted" style="letter-spacing: 0.14em">Recipes</div>
                    </div>
                    <div class="flex-shrink-0">
                      <div class="h3 fw-bold cn-headline mb-0" style="color: var(--cn-primary)"><?php echo number_format($cn_stat_followers); ?></div>
                      <div class="small text-uppercase fw-bold cn-text-muted" style="letter-spacing: 0.14em">Followers</div>
                    </div>
                    <div class="flex-shrink-0">
                      <div class="h3 fw-bold cn-headline mb-0" style="color: var(--cn-primary)"><?php echo number_format($cn_stat_saved); ?></div>
                      <div class="small text-uppercase fw-bold cn-text-muted" style="letter-spacing: 0.14em">Saved</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12 col-md-4">
                <a class="btn cn-btn-primary w-100 py-3 cn-editorial-shadow" href="create-recipe.php">
                  <span class="material-symbols-outlined align-middle me-1">add_circle</span>
                  Create New Recipe
                </a>
              </div>
            </div>
          </header>

          <section class="row g-4">
            <div class="col-12 col-xl-8">
              <div class="d-flex justify-content-between align-items-end mb-3">
                <h2 class="h4 fw-semibold cn-headline mb-0" style="color: var(--cn-primary)">My Recipes</h2>
                <a class="btn btn-link text-decoration-none fw-bold p-0" href="user-recipes.php" style="color: var(--cn-primary); font-size: 0.75rem; letter-spacing: 0.12em">
                  VIEW ALL
                </a>
              </div>

              <div class="row g-4">
                <?php if (count($cn_my_recipes) === 0) : ?>
                  <div class="col-12">
                    <div class="cn-card cn-card--lowest rounded-4 p-4 p-md-5 text-center">
                      <p class="cn-text-muted mb-3 mb-md-4">You have not published any recipes yet. Create your first one for the community.</p>
                      <a class="btn cn-btn-primary cn-editorial-shadow px-4 py-3" href="create-recipe.php">Create recipe</a>
                    </div>
                  </div>
                <?php else : ?>
                  <?php foreach ($cn_my_recipes as $cn_r) : ?>
                    <?php
                    $cn_img = !empty($cn_r['hero_image']) ? (string) $cn_r['hero_image'] : $cn_placeholder_food;
                    $cn_rating = isset($cn_r['rating_avg']) ? (float) $cn_r['rating_avg'] : 0.0;
                    $cn_rcount = (int) ($cn_r['rating_count'] ?? 0);
                    $cn_show_rating = $cn_rcount > 0 && $cn_rating > 0;
                    $cn_prep = isset($cn_r['prep_minutes']) && $cn_r['prep_minutes'] !== null ? (int) $cn_r['prep_minutes'] : null;
                    $cn_id = (int) $cn_r['id'];
                    $cn_detail_href = 'recipe-details.php?id=' . $cn_id;
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
                            <div class="small cn-text-muted fst-italic">Updated <?php echo htmlspecialchars(cn_dashboard_time_ago(isset($cn_r['updated_at']) ? (string) $cn_r['updated_at'] : null), ENT_QUOTES, 'UTF-8'); ?></div>
                            <a class="btn rounded-circle p-2" style="background: rgba(161, 212, 148, 0.18); color: var(--cn-primary)" aria-label="View recipe" href="<?php echo htmlspecialchars($cn_detail_href, ENT_QUOTES, 'UTF-8'); ?>">
                              <span class="material-symbols-outlined">visibility</span>
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-12 col-xl-4">
              <div class="d-flex justify-content-between align-items-end mb-3">
                <h2 class="h4 fw-semibold cn-headline mb-0" style="color: var(--cn-primary)">Saved Favorites</h2>
              </div>

              <div class="d-grid gap-3">
                <?php if (count($cn_saved_rows) === 0) : ?>
                  <div class="cn-card cn-card--lowest p-4 rounded-4 small cn-text-muted text-center">No saved recipes yet. Browse the community and tap the heart on a recipe.</div>
                <?php else : ?>
                  <?php foreach ($cn_saved_rows as $cn_s) : ?>
                    <?php
                    $cn_sim = !empty($cn_s['hero_image']) ? (string) $cn_s['hero_image'] : $cn_placeholder_food;
                    $cn_shref = 'recipe-details.php?slug=' . rawurlencode((string) $cn_s['slug']);
                    ?>
                    <div class="cn-card cn-card--lowest p-3 rounded-4 d-flex align-items-center gap-3">
                      <a class="rounded-3 overflow-hidden flex-shrink-0" style="width: 64px; height: 64px" href="<?php echo htmlspecialchars($cn_shref, ENT_QUOTES, 'UTF-8'); ?>">
                        <img
                          alt=""
                          src="<?php echo htmlspecialchars($cn_sim, ENT_QUOTES, 'UTF-8'); ?>"
                          class="w-100 h-100 object-fit-cover"
                        />
                      </a>
                      <div class="flex-grow-1 min-w-0">
                        <a class="text-reset text-decoration-none" href="<?php echo htmlspecialchars($cn_shref, ENT_QUOTES, 'UTF-8'); ?>">
                          <div class="fw-bold cn-headline text-truncate" style="color: var(--cn-primary); font-size: 0.95rem"><?php echo htmlspecialchars((string) $cn_s['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </a>
                        <div class="small cn-text-muted">by <?php echo htmlspecialchars((string) $cn_s['author_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                      </div>
                      <span class="p-0" style="color: var(--cn-secondary-container)" aria-hidden="true">
                        <span class="material-symbols-outlined cn-fill">favorite</span>
                      </span>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>

              <!--<div class="mt-4 p-4 rounded-4 position-relative overflow-hidden cn-editorial-shadow" style="background: #724600; color: #fff">
                <div class="position-relative">
                  <div class="fw-bold cn-headline mb-1">Need Inspiration?</div>
                  <div class="small opacity-75 mb-3">Explore seasonal ingredients recommended for you.</div>
                  <button class="btn cn-btn-pill px-4 py-2 fw-bold" style="background: #fff; color: #724600; font-size: 0.7rem; letter-spacing: 0.12em">
                    DISCOVER NOW
                  </button>
                </div>
                <span class="material-symbols-outlined position-absolute" style="right: -18px; bottom: -24px; font-size: 120px; opacity: 0.12">
                  restaurant_menu
                </span>
              </div>-->
            </div>
          </section>

          <footer class="rounded-4 p-4 p-md-5 mt-5" style="background: #05220a; color: rgba(240, 255, 240, 0.86)">
            <div class="row g-3 align-items-center">
              <div class="col-12 col-md-4 text-center text-md-start">
                <div class="fw-bold cn-headline text-white">CookNet</div>
                <div class="small" style="color: rgba(240, 255, 240, 0.6)">© 2024 CookNet. The Artisanal Editorial Experience.</div>
              </div>
              <div class="col-12 col-md-4 d-flex justify-content-center gap-4 small">
                <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">About CookNet</a>
                <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Editorial Policy</a>
                <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Privacy</a>
              </div>
              <div class="col-12 col-md-4 d-flex justify-content-center justify-content-md-end gap-3">
                <button class="btn btn-link text-decoration-none p-0" style="color: rgba(240, 255, 240, 0.65)" aria-label="Share">
                  <span class="material-symbols-outlined">share</span>
                </button>
                <button class="btn btn-link text-decoration-none p-0" style="color: rgba(240, 255, 240, 0.65)" aria-label="Mail">
                  <span class="material-symbols-outlined">mail</span>
                </button>
              </div>
            </div>
          </footer>
          <div class="cn-mobile-nav-spacer d-md-none"></div>
        </main>
      </div>
    </div>

    <div class="d-md-none fixed-bottom bg-white shadow-lg">
      <div class="d-flex justify-content-between px-3 py-2">
        <a class="text-decoration-none text-center cn-text-muted" href="index.php" data-nav style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">home</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">HOME</div>
        </a>
        <a class="text-decoration-none text-center cn-text-muted" href="index.php" style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">search</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">SEARCH</div>
        </a>
        <a class="text-decoration-none text-center" href="dashboard.php" data-nav style="color: var(--cn-primary)">
          <div><span class="material-symbols-outlined cn-fill">dashboard</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">DASHBOARD</div>
        </a>
        <a class="text-decoration-none text-center cn-text-muted" href="dashboard.php" style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">person</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">PROFILE</div>
        </a>
      </div>
    </div>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>
    <script src="assets/js/app.js"></script>
  </body>
</html>

