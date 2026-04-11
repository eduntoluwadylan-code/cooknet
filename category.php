<?php
$cn_nav_active = 'browse';
$cn_nav_search = true;
$cn_submit_recipe_disabled = false;
$cn_sidebar_sticky_top = '96px';

require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/category_recipes_query.php';

$pdo = cn_pdo();
$cn_cat_id = isset($_GET['id']) ? (int) $_GET['id'] : 0; 
if ($cn_cat_id < 1) {
    $cn_category = null;
    $cn_recipes = [];
    $cn_category_missing = true;
    $cn_category_active = '';
} else {
    $cn_category_missing = false;
    $cn_category = cn_category_by_id($pdo, $cn_cat_id);
    $cn_recipes = $cn_category ? cn_recipes_for_category($pdo, $cn_cat_id) : [];
    if (!$cn_category) {
        http_response_code(404);
        $cn_category_active = '';
    } else {
        $cn_category_active = $cn_category['slug'];
    }
}

$cn_page_title = 'CookNet | Categories';
if ($cn_category !== null) {
    $cn_page_title = 'CookNet | ' . $cn_category['label'];
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

    <div class="container-fluid" style="padding-top: 88px">
      <div class="row gx-4">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <main class="col-12 col-lg-9 col-xl-10">
          <?php if ($cn_category_missing) : ?>
            <section class="cn-card cn-card--lowest rounded-4 p-5 p-md-5 text-center mb-5">
              <span class="material-symbols-outlined mb-3 d-block" style="font-size: 3rem; color: var(--cn-primary)">category</span>
              <h1 class="h3 fw-bold cn-headline mb-3" style="color: var(--cn-primary)">Choose a category</h1>
              <p class="cn-text-muted mb-4 mb-md-5">Pick a collection from the sidebar (desktop) or open the menu (mobile) to browse recipes by category.</p>
              <a class="btn cn-btn-primary cn-editorial-shadow px-4 py-3" href="index.php">Back to home</a>
            </section>
          <?php elseif ($cn_category === null) : ?>
            <section class="cn-card cn-card--lowest rounded-4 p-5 p-md-5 text-center mb-5">
              <h1 class="h3 fw-bold cn-headline mb-3" style="color: var(--cn-primary)">Category not found</h1>
              <p class="cn-text-muted mb-4 mb-md-5">This category does not exist or was removed.</p>
              <a class="btn cn-btn-primary cn-editorial-shadow px-4 py-3" href="index.php">Back to home</a>
            </section>
          <?php else : ?>
            <section class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
              <div class="d-flex align-items-start gap-3">
                <span class="material-symbols-outlined d-none d-sm-inline" style="font-size: 2.5rem; color: var(--cn-primary)"><?php echo htmlspecialchars($cn_category['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                <div>
                  <h1 class="h2 fw-bold cn-headline mb-1" style="color: var(--cn-primary)"><?php echo htmlspecialchars($cn_category['label'], ENT_QUOTES, 'UTF-8'); ?></h1>
                  <p class="mb-0 cn-text-muted">Published recipes in this collection.</p>
                </div>
              </div>
            </section>

            <?php if (count($cn_recipes) === 0) : ?>
              <section class="cn-card cn-card--lowest rounded-4 p-5 p-md-5 text-center mb-5">
                <span class="material-symbols-outlined mb-3 d-block" style="font-size: 3rem; color: var(--cn-on-surface-variant)">restaurant</span>
                <h2 class="h4 fw-bold cn-headline mb-3" style="color: var(--cn-primary)">No recipes yet</h2>
                <p class="cn-text-muted mb-4 mb-md-5">There are no published dishes in this category. Check back soon or explore another collection.</p>
                <a class="btn cn-btn-secondary px-4 py-3 me-2" href="index.php">Home</a>
                <?php if (empty($cn_submit_recipe_disabled)) : ?>
                  <a class="btn cn-btn-primary cn-editorial-shadow px-4 py-3" href="create-recipe.php">Submit a recipe</a>
                <?php endif; ?>
              </section>
            <?php else : ?>
              <section class="row g-4 mb-5">
                <?php foreach ($cn_recipes as $cn_r) : ?>
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
                  ?>
                  <div class="col-12 col-md-6 col-lg-4">
                    <a class="text-decoration-none text-reset" href="<?php echo htmlspecialchars($cn_href, ENT_QUOTES, 'UTF-8'); ?>">
                      <div class="ratio ratio-4x3 rounded-4 overflow-hidden mb-3 position-relative">
                        <img
                          src="<?php echo htmlspecialchars($cn_img, ENT_QUOTES, 'UTF-8'); ?>"
                          alt=""
                          class="w-100 h-100 object-fit-cover"
                        />
                        <?php if ($cn_feat) : ?>
                          <div class="position-absolute top-0 start-0 p-3">
                            <span class="cn-chip" style="background: var(--cn-secondary-container); color: #663100">Chef's choice</span>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="px-1">
                        <!--<div class="d-flex justify-content-between align-items-start gap-2">
                          <h2 class="h5 fw-bold cn-headline mb-1"><?php echo htmlspecialchars($cn_title, ENT_QUOTES, 'UTF-8'); ?></h2>
                          <span class="material-symbols-outlined cn-text-muted" aria-hidden="true">bookmark</span>
                        </div>-->
                        <?php if ($cn_desc_short !== '') : ?>
                          <p class="cn-text-muted mb-2"><?php echo htmlspecialchars($cn_desc_short, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <div class="d-flex flex-wrap gap-3 small fw-bold" style="color: var(--cn-primary)">
                          <?php if ($cn_time_label !== null) : ?>
                            <span><span class="material-symbols-outlined align-middle" style="font-size: 18px">timer</span> <?php echo htmlspecialchars($cn_time_label, ENT_QUOTES, 'UTF-8'); ?></span>
                          <?php endif; ?>
                          <span>
                            <span class="material-symbols-outlined cn-fill align-middle" style="font-size: 18px">star</span>
                            <?php echo htmlspecialchars(number_format($cn_rating, 1), ENT_QUOTES, 'UTF-8'); ?>
                            (<?php echo (string) $cn_rcount; ?>)
                          </span>
                        </div>
                      </div>
                    </a>
                  </div>
                <?php endforeach; ?>
              </section>
            <?php endif; ?>
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
        <a class="text-decoration-none text-center" href="index.php" data-nav style="color: var(--cn-primary)">
          <div><span class="material-symbols-outlined cn-fill">explore</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Browse</div>
        </a>
        <a class="text-decoration-none text-center cn-text-muted" href="recipe-details.php" data-nav style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">bookmark</span></div>
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
