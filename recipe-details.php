<?php
$cn_nav_active = 'seasonal';
$cn_nav_search = false;
$cn_submit_recipe_disabled = false;
$cn_sidebar_sticky_top = '104px';

require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/recipe_detail_query.php';

$pdo = cn_pdo();
$cn_recipe_id_get = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$cn_recipe_slug_get = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
if ($cn_recipe_id_get < 1) {
    $cn_recipe_id_get = 0;
}
if ($cn_recipe_slug_get === '') {
    $cn_recipe_slug_get = null;
}

$cn_detail = null;
if ($cn_recipe_id_get > 0) {
    $cn_detail = cn_recipe_detail_load($pdo, $cn_recipe_id_get, null);
} elseif ($cn_recipe_slug_get !== null) {
    $cn_detail = cn_recipe_detail_load($pdo, null, $cn_recipe_slug_get);
}

$cn_category_active = ($cn_detail !== null && isset($cn_detail['recipe']['category_slug']))
    ? (string) $cn_detail['recipe']['category_slug']
    : 'main_dishes';

$cn_page_title = 'CookNet | Recipe';
if ($cn_detail !== null) {
    $cn_page_title = 'CookNet | ' . (string) $cn_detail['recipe']['title'];
}

$cn_recipe_not_found = $cn_detail === null && ($cn_recipe_id_get > 0 || $cn_recipe_slug_get !== null);
if ($cn_recipe_not_found) {
    http_response_code(404);
}

$cn_recipe_nav_href = 'recipe-details.php';
if ($cn_detail !== null) {
    $cn_recipe_nav_href = 'recipe-details.php?slug=' . rawurlencode((string) $cn_detail['recipe']['slug']);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <title><?php echo htmlspecialchars($cn_page_title, ENT_QUOTES, 'UTF-8'); ?></title>
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

    <?php
    $cn_favorite_flash_ok = cn_flash_get('favorite_ok');
    $cn_favorite_flash_err = cn_flash_get('favorite_error');
    ?>
    <?php if ($cn_favorite_flash_ok !== null) : ?>
      <div
        class="alert alert-success py-2 px-3 rounded-0 border-0 text-center small mb-0"
        role="alert"
        style="position: fixed; top: 76px; left: 0; right: 0; z-index: 1030"
      >
        <?php echo htmlspecialchars($cn_favorite_flash_ok, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>
    <?php if ($cn_favorite_flash_err !== null) : ?>
      <div
        class="alert alert-danger py-2 px-3 rounded-0 border-0 text-center small mb-0"
        role="alert"
        style="position: fixed; top: 76px; left: 0; right: 0; z-index: 1030"
      >
        <?php echo htmlspecialchars($cn_favorite_flash_err, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <main class="container-fluid" style="padding-top: 96px">
      <div class="row gx-4">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <div class="col-12 col-lg-9 col-xl-10">
          <?php if ($cn_detail === null) : ?>
            <div class="cn-card cn-card--lowest rounded-4 p-5 p-md-5 text-center mb-4">
              <?php if ($cn_recipe_not_found) : ?>
                <h2 class="h4 fw-bold cn-headline mb-3" style="color: var(--cn-primary)">Recipe not found</h2>
                <p class="cn-text-muted mb-4">That recipe does not exist, or it is not published yet.</p>
              <?php else : ?>
                <h2 class="h4 fw-bold cn-headline mb-3" style="color: var(--cn-primary)">Open a recipe</h2>
                <p class="cn-text-muted mb-4">Use <code class="small">recipe-details.php?slug=your-recipe-slug</code> or <code class="small">?id=123</code>, or browse from the home page.</p>
              <?php endif; ?>
              <a class="btn cn-btn-primary cn-editorial-shadow px-4 py-3" href="index.php">Browse recipes</a>
            </div>
          <?php else : ?>
            <?php
            $cn_r = $cn_detail['recipe'];
            $cn_au = $cn_detail['author'];
            $cn_hero = $cn_detail['hero_url'];
            $cn_ings = $cn_detail['ingredients'];
            $cn_steps_list = $cn_detail['steps'];
            $cn_prep_m = isset($cn_r['prep_minutes']) && $cn_r['prep_minutes'] !== null && $cn_r['prep_minutes'] !== '' ? (int) $cn_r['prep_minutes'] : null;
            $cn_cook_m = isset($cn_r['cook_minutes']) && $cn_r['cook_minutes'] !== null && $cn_r['cook_minutes'] !== '' ? (int) $cn_r['cook_minutes'] : null;
            $cn_av = !empty($cn_au['avatar_url'])
                ? (string) $cn_au['avatar_url']
                : 'https://ui-avatars.com/api/?name=' . rawurlencode((string) $cn_au['display_name']) . '&background=154212&color=fff&size=128';
            $cn_feat = !empty($cn_r['featured']) && (int) $cn_r['featured'] === 1;
            $cn_draft = ($cn_r['status'] ?? '') === 'draft';
            $cn_viewer_id = cn_user_id();
            $cn_recipe_pk = (int) $cn_r['id'];
            $cn_owner_uid = (int) $cn_r['user_id'];
            $cn_is_owner = $cn_viewer_id !== null && $cn_viewer_id === $cn_owner_uid;
            $cn_is_favorited = false;
            if ($cn_viewer_id !== null && !$cn_is_owner) {
                $cn_st_fav = $pdo->prepare('SELECT 1 FROM user_favorites WHERE user_id = ? AND recipe_id = ? LIMIT 1');
                $cn_st_fav->execute([$cn_viewer_id, $cn_recipe_pk]);
                $cn_is_favorited = (bool) $cn_st_fav->fetchColumn();
            }
            $cn_favorite_next = 'recipe-details.php?slug=' . rawurlencode((string) $cn_r['slug']);
            $cn_favorite_csrf = cn_csrf_token();
            ?>
          <section class="row g-4 align-items-end mb-4">
            <div class="col-12 col-xl-8">
              <div class="rounded-4 overflow-hidden cn-editorial-shadow position-relative">
                <img
                  src="<?php echo htmlspecialchars($cn_hero, ENT_QUOTES, 'UTF-8'); ?>"
                  alt=""
                  class="w-100 object-fit-cover"
                  style="height: 520px"
                />
                <div class="position-absolute bottom-0 start-0 w-100 p-4 p-md-5" style="background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.55) 70%, rgba(0,0,0,0.7) 100%)">
                  <?php if ($cn_feat) : ?>
                    <span class="cn-chip me-2" style="background: var(--cn-secondary-container); color: #fff">Chef's Choice</span>
                  <?php endif; ?>
                  <span class="cn-chip cn-chip--secondary"><?php echo htmlspecialchars((string) $cn_r['category_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>
            </div>
            <div class="col-12 col-xl-4">
              <h1 class="display-6 fw-bold cn-headline mb-2" style="color: var(--cn-primary); line-height: 1.05">
                <?php echo htmlspecialchars((string) $cn_r['title'], ENT_QUOTES, 'UTF-8'); ?>
              </h1>
              <?php if ($cn_draft) : ?>
                <div class="mb-2"><span class="cn-chip cn-chip--tertiary">Draft</span></div>
              <?php endif; ?>
              <?php if (!empty($cn_r['description'])) : ?>
                <p class="cn-text-muted mb-4"><?php echo nl2br(htmlspecialchars((string) $cn_r['description'], ENT_QUOTES, 'UTF-8')); ?></p>
              <?php endif; ?>
              <div class="d-flex align-items-center gap-3 mb-4">
                <img
                  src="<?php echo htmlspecialchars($cn_av, ENT_QUOTES, 'UTF-8'); ?>"
                  alt=""
                  class="rounded-circle object-fit-cover"
                  style="width: 52px; height: 52px"
                />
                <div>
                  <div class="fw-bold"><?php echo htmlspecialchars((string) $cn_au['display_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="small cn-text-muted"><?php echo htmlspecialchars(cn_recipe_author_label((string) ($cn_au['role'] ?? 'user')), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-4 mb-4">
                <div>
                  <div class="small fw-bold text-uppercase" style="letter-spacing: 0.14em; color: rgba(114, 121, 110, 0.9)">Prep Time</div>
                  <div class="h5 fw-bold mb-0" style="color: var(--cn-tertiary)"><?php echo htmlspecialchars(cn_recipe_format_minutes($cn_prep_m), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div>
                  <div class="small fw-bold text-uppercase" style="letter-spacing: 0.14em; color: rgba(114, 121, 110, 0.9)">Cook Time</div>
                  <div class="h5 fw-bold mb-0" style="color: var(--cn-tertiary)"><?php echo htmlspecialchars(cn_recipe_format_minutes($cn_cook_m), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div>
                  <div class="small fw-bold text-uppercase" style="letter-spacing: 0.14em; color: rgba(114, 121, 110, 0.9)">Level</div>
                  <div class="h5 fw-bold mb-0" style="color: var(--cn-tertiary)"><?php echo htmlspecialchars(cn_recipe_difficulty_label(isset($cn_r['difficulty']) ? (string) $cn_r['difficulty'] : null), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>

              <?php if ($cn_is_owner) : ?>
                <a
                  class="btn cn-btn-secondary w-100 py-3"
                  href="edit-recipe.php?id=<?php echo (string) (int) $cn_recipe_pk; ?>"
                >
                  <span class="material-symbols-outlined align-middle me-1">edit</span>
                  Edit recipe
                </a>
              <?php elseif ($cn_viewer_id === null) : ?>
                <a
                  class="btn cn-btn-primary w-100 py-3 cn-editorial-shadow"
                  href="index.php?login=1&amp;next=<?php echo rawurlencode($cn_favorite_next); ?>"
                >
                  <span class="material-symbols-outlined cn-fill align-middle me-1">favorite</span>
                  Sign in to save
                </a>
              <?php elseif ($cn_is_favorited) : ?>
                <form method="post" action="favorite-recipe.php" class="mb-0">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cn_favorite_csrf, ENT_QUOTES, 'UTF-8'); ?>" />
                  <input type="hidden" name="action" value="remove" />
                  <input type="hidden" name="recipe_id" value="<?php echo (string) $cn_recipe_pk; ?>" />
                  <input type="hidden" name="next" value="<?php echo htmlspecialchars($cn_favorite_next, ENT_QUOTES, 'UTF-8'); ?>" />
                  <button type="submit" class="btn btn-outline-secondary w-100 py-3 border-2" title="Remove from saved recipes">
                    <span class="material-symbols-outlined align-middle me-1">bookmark_remove</span>
                    Remove from favorites
                  </button>
                </form>
              <?php else : ?>
                <form method="post" action="favorite-recipe.php" class="mb-0">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cn_favorite_csrf, ENT_QUOTES, 'UTF-8'); ?>" />
                  <input type="hidden" name="action" value="add" />
                  <input type="hidden" name="recipe_id" value="<?php echo (string) $cn_recipe_pk; ?>" />
                  <input type="hidden" name="next" value="<?php echo htmlspecialchars($cn_favorite_next, ENT_QUOTES, 'UTF-8'); ?>" />
                  <button type="submit" class="btn cn-btn-primary w-100 py-3 cn-editorial-shadow">
                    <span class="material-symbols-outlined cn-fill align-middle me-1">favorite</span>
                    Save to Favorites
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </section>

          <section class="row g-4 g-xl-5">
            <div class="col-12 col-xl-4">
              <h2 class="h4 fw-bold cn-headline mb-3" style="color: var(--cn-primary)">
                <span class="material-symbols-outlined align-middle me-1">shopping_basket</span> Ingredients
              </h2>

              <?php if (count($cn_ings) === 0) : ?>
                <p class="cn-text-muted small mb-0">No ingredients listed yet.</p>
              <?php else : ?>
                <div class="d-grid gap-3">
                  <?php foreach ($cn_ings as $cn_i => $cn_ing) : ?>
                    <?php
                    $cn_q = isset($cn_ing['quantity']) ? trim((string) $cn_ing['quantity']) : '';
                    $cn_nm = isset($cn_ing['name']) ? trim((string) $cn_ing['name']) : '';
                    $cn_card_low = ($cn_i % 2) === 1;
                    ?>
                    <div class="p-3 rounded-4 d-flex align-items-center gap-3 <?php echo $cn_card_low ? 'cn-card cn-card--lowest' : 'cn-card'; ?>">
                      <div class="rounded-circle fw-bold d-flex align-items-center justify-content-center flex-shrink-0 small" style="width: 42px; height: 42px; background: #ffdcc5; color: #301400">
                        <?php echo $cn_q !== '' ? htmlspecialchars($cn_q, ENT_QUOTES, 'UTF-8') : '•'; ?>
                      </div>
                      <div><?php echo htmlspecialchars($cn_nm, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="col-12 col-xl-8">
              <h2 class="h4 fw-bold cn-headline mb-3" style="color: var(--cn-primary)">
                <span class="material-symbols-outlined align-middle me-1">menu_book</span> Step-by-Step
              </h2>

              <?php if (count($cn_steps_list) === 0) : ?>
                <p class="cn-text-muted small mb-0">No steps listed yet.</p>
              <?php else : ?>
              <div class="position-relative">
                <div class="position-absolute d-none d-sm-block" style="left: 22px; top: 0; bottom: 0; width: 1px; background: rgba(194, 201, 187, 0.35)"></div>

                <div class="d-grid gap-4">
                  <?php foreach ($cn_steps_list as $cn_st) : ?>
                    <?php
                    $cn_sn = isset($cn_st['step_number']) ? (int) $cn_st['step_number'] : 0;
                    $cn_lbl = $cn_sn > 0 ? str_pad((string) $cn_sn, 2, '0', STR_PAD_LEFT) : '—';
                    $cn_inst = isset($cn_st['instruction']) ? (string) $cn_st['instruction'] : '';
                    ?>
                  <div class="d-flex align-items-center gap-3 ps-0 ps-sm-5">
                    <div
                      class="d-none d-sm-flex flex-shrink-0 rounded-circle align-items-center justify-content-center fw-bold small"
                      style="width: 46px; height: 46px; background: var(--cn-primary); color: #fff"
                    >
                      <?php echo htmlspecialchars($cn_lbl, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <p class="cn-text-muted mb-0 flex-grow-1 min-w-0"><?php echo nl2br(htmlspecialchars($cn_inst, ENT_QUOTES, 'UTF-8')); ?></p>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>

              <!--<div class="mt-5 p-4 p-md-5 rounded-4 cn-card cn-card--lowest cn-editorial-shadow">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h2 class="h4 fw-bold cn-headline mb-0" style="color: var(--cn-primary)">Community Tips</h2>
                  <button class="btn btn-link text-decoration-none fw-bold p-0" style="color: var(--cn-primary)">Add a tip</button>
                </div>

                <div class="d-grid gap-3">
                  <div class="d-flex gap-3">
                    <img
                      alt="User"
                      src="https://lh3.googleusercontent.com/aida-public/AB6AXuAz4X5ltDCTbot8bQ8tObsUdZU_S3dGOuTuTxo-1qqNppcP6VkMjo-7CDkAA6WIa8gDlvh_AObonLW2QEBv2PvT91JASj3XKiU34XwISVQMNjCMmLm6cn8WRTq8BxcFnl87peRDMaFENYlRr8Y8B5ganuBRGM05i-jQDhvYEAxGYFp6-m56MeEs1FjT_6fqk4oNCV8Yx67QbgAFOAf_Ix-YlmotmEZmsefVeHMmKMupN4nWHo32Efm6tNtcToy7mYoRDi0Mp3sgRo7x"
                      class="rounded-circle object-fit-cover"
                      style="width: 42px; height: 42px"
                    />
                    <div class="cn-card p-3 rounded-4 flex-grow-1">
                      <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <div class="fw-bold">Marcus Chen</div>
                        <div class="small cn-text-muted">2 days ago</div>
                      </div>
                      <div class="cn-text-muted">Adding some parsnips along with the carrots really elevated the sweetness of the pan drippings. Highly recommend!</div>
                    </div>
                  </div>
                  <div class="d-flex gap-3">
                    <img
                      alt="User"
                      src="https://lh3.googleusercontent.com/aida-public/AB6AXuBJ0FUdePgk1TErLoS-d2lWuzPCdCkjlXyxBQ4K2g8zOPeNK1KBlr_OXUZtroPUpHIW1VSC9XYldbq1rfMDJh8WnBzwHP_RTQJ6DnA1jrNf7fyxDAOol7xlScFzsoJTt5U8xrvr1-v3DeicpAUEX-cnZ0LbptyT-focQo3Nmln6Xu5xxNbc2ONTq6lYNSliaMitMcSiXYiFj-ORf-xRgCrHjXg6r3l6GsDxzWWhy0YsbMW3XOlwIpXZnp1Z9ColZb2ClL4wijDEe3_l"
                      class="rounded-circle object-fit-cover"
                      style="width: 42px; height: 42px"
                    />
                    <div class="cn-card p-3 rounded-4 flex-grow-1">
                      <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <div class="fw-bold">Sarah Jenkins</div>
                        <div class="small cn-text-muted">5 days ago</div>
                      </div>
                      <div class="cn-text-muted">Make sure you actually dry the skin. I skipped this once and it just wasn't the same. The paper towel trick is essential!</div>
                    </div>
                  </div>
                </div>
              </div>-->
            </div>
          </section>

          <?php endif; ?>

          <footer class="rounded-4 p-4 p-md-5 mt-5" style="background: #05220a; color: rgba(240, 255, 240, 0.86)">
            <div class="row g-3 align-items-center">
              <div class="col-12 col-md-4">
                <div class="fw-bold cn-headline text-white">CookNet</div>
                <div class="small" style="color: rgba(240, 255, 240, 0.6)">© 2024 CookNet. The Artisanal Editorial Experience.</div>
              </div>
              <div class="col-12 col-md-4 text-md-center d-flex justify-content-md-center gap-3 small">
                <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">About CookNet</a>
                <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Editorial Policy</a>
                <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Contact Us</a>
              </div>
              <div class="col-12 col-md-4 text-md-end d-flex justify-content-md-end gap-3 small">
                <span class="material-symbols-outlined" style="opacity: 0.85">social_leaderboard</span>
                <span class="material-symbols-outlined" style="opacity: 0.85">potted_plant</span>
                <span class="material-symbols-outlined" style="opacity: 0.85">camera</span>
              </div>
            </div>
          </footer>
          <div class="cn-mobile-nav-spacer d-md-none"></div>
        </div>
      </div>
    </main>

    <div class="d-md-none fixed-bottom bg-white shadow-lg">
      <div class="d-flex justify-content-around py-2">
        <a class="text-decoration-none text-center cn-text-muted" href="index.php" data-nav style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">home</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Home</div>
        </a>
        <a class="text-decoration-none text-center cn-text-muted" href="index.php" style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">search</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Search</div>
        </a>
        <a class="text-decoration-none text-center" href="<?php echo htmlspecialchars($cn_recipe_nav_href, ENT_QUOTES, 'UTF-8'); ?>" data-nav style="color: var(--cn-primary)">
          <div><span class="material-symbols-outlined cn-fill">bookmark</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Saved</div>
        </a>
        <a class="text-decoration-none text-center cn-text-muted" href="dashboard.php" data-nav style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">person</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Profile</div>
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

