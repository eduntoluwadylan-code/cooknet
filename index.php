<?php
$cn_nav_active = 'browse';
$cn_nav_search = true;
$cn_category_active = 'main_dishes';
$cn_submit_recipe_disabled = false;
$cn_sidebar_sticky_top = '96px';
require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_conn.php';
require_once __DIR__ . '/includes/category_recipes_query.php';
require_once __DIR__ . '/services/HomeFeedService.php';

$pdo = cn_pdo();
$cn_home = new HomeFeedService();
$cn_index_sort = isset($_GET['sort']) && (string) $_GET['sort'] === 'favorites' ? 'favorites' : 'all';
$cn_spotlight = $cn_home->spotlightRecipe($pdo);
$cn_grid_recipes = $cn_home->listRecipes($pdo, $cn_index_sort, 24);
?>
<!doctype html>
<html lang="en">
  <head>
    <title>CookNet | The Artisanal Editorial Experience</title>
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

        <!-- Main -->
        <main class="col-12 col-lg-9 col-xl-10">
          <!-- Hero: featured / latest published -->
          <section class="cn-hero position-relative mb-5 cn-editorial-shadow">
            <?php if ($cn_spotlight !== null) : ?>
              <?php
              $cn_sp_img = cn_recipe_resolve_asset_url(isset($cn_spotlight['hero_path']) ? (string) $cn_spotlight['hero_path'] : '');
              $cn_sp_title = (string) $cn_spotlight['title'];
              $cn_sp_slug = (string) $cn_spotlight['slug'];
              $cn_sp_author = (string) ($cn_spotlight['author_name'] ?? 'Chef');
              $cn_sp_mins = cn_recipe_card_minutes_total($cn_spotlight);
              $cn_sp_time = $cn_sp_mins !== null ? cn_recipe_format_minutes($cn_sp_mins) : '—';
              ?>
            <img
              src="<?php echo htmlspecialchars($cn_sp_img, ENT_QUOTES, 'UTF-8'); ?>"
              alt=""
              class="w-100 h-100 object-fit-cover"
              style="min-height: 500px"
            />
            <div class="position-absolute inset-0 top-0 start-0 w-100 h-100 cn-hero-overlay"></div>
            <div class="position-absolute bottom-0 start-0 p-4 p-md-5" style="max-width: 860px">
              <span class="cn-chip cn-chip--secondary mb-3" style="background: var(--cn-secondary-container); color: #663100">Recipe of the day</span>
              <h1 class="display-5 display-md-4 fw-extrabold text-white mb-3" style="line-height: 1.05">
                <?php echo htmlspecialchars($cn_sp_title, ENT_QUOTES, 'UTF-8'); ?>
              </h1>
              <div class="d-flex flex-wrap align-items-center gap-3 gap-md-4 text-white-50">
                <div class="d-flex align-items-center gap-2 text-white">
                  <span class="material-symbols-outlined" style="color: rgba(255, 183, 131, 1)">schedule</span>
                  <span class="fw-semibold"><?php echo htmlspecialchars($cn_sp_time, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="d-flex align-items-center gap-2 text-white">
                  <span class="material-symbols-outlined" style="color: rgba(255, 183, 131, 1)">person</span>
                  <span class="fw-semibold"><?php echo htmlspecialchars($cn_sp_author, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <a
                  href="recipe-details.php?slug=<?php echo rawurlencode($cn_sp_slug); ?>"
                  class="btn btn-sm rounded-pill px-4 py-2 text-white"
                  style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.28)"
                >
                  <span class="fw-bold">View Recipe</span>
                  <span class="material-symbols-outlined align-middle ms-1" style="font-size: 18px">arrow_forward</span>
                </a>
              </div>
            </div>
            <?php else : ?>
            <img
              src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=1600&q=80&auto=format&fit=crop"
              alt=""
              class="w-100 h-100 object-fit-cover"
              style="min-height: 500px"
            />
            <div class="position-absolute inset-0 top-0 start-0 w-100 h-100 cn-hero-overlay"></div>
            <div class="position-absolute bottom-0 start-0 p-4 p-md-5" style="max-width: 860px">
              <span class="cn-chip cn-chip--secondary mb-3" style="background: var(--cn-secondary-container); color: #663100">Recipe of the day</span>
              <h1 class="display-5 display-md-4 fw-extrabold text-white mb-3" style="line-height: 1.05">No published recipes yet</h1>
              <p class="text-white mb-4" style="opacity: 0.9">Be the first to share a dish with the community.</p>
              <?php if (empty($cn_submit_recipe_disabled)) : ?>
                <a
                  href="create-recipe.php"
                  class="btn btn-sm rounded-pill px-4 py-2 text-white"
                  style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.28)"
                >
                  <span class="fw-bold">Submit a recipe</span>
                  <span class="material-symbols-outlined align-middle ms-1" style="font-size: 18px">arrow_forward</span>
                </a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </section>

          <!-- Header -->
          <section class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
              <h2 class="h2 fw-bold cn-headline mb-1" style="color: var(--cn-primary)">Artisanal Explorations</h2>
              <p class="mb-0 cn-text-muted">Hand-picked by our community of professional chefs and home enthusiasts.</p>
            </div>
            <div class="d-flex gap-2 overflow-auto pb-2">
              <a
                class="btn cn-btn-pill px-4 py-2 text-decoration-none"
                href="index.php"
                style="<?php echo $cn_index_sort === 'all' ? 'background: var(--cn-primary); color: #fff' : 'background: var(--cn-surface-container-high); color: var(--cn-on-surface-variant)'; ?>"
              >All Recipes</a>
              <a
                class="btn cn-btn-pill px-4 py-2 text-decoration-none"
                href="index.php?sort=favorites"
                style="<?php echo $cn_index_sort === 'favorites' ? 'background: var(--cn-primary); color: #fff' : 'background: var(--cn-surface-container-high); color: var(--cn-on-surface-variant)'; ?>"
              >Most Favorited</a>
            </div>
          </section>

          <!-- Grid -->
          <section class="row g-4 mb-5">
            <?php if (count($cn_grid_recipes) === 0) : ?>
            <div class="col-12">
              <div class="cn-card cn-card--lowest rounded-4 p-5 text-center">
                <p class="cn-text-muted mb-3 mb-md-4">
                  <?php if ($cn_index_sort === 'favorites') : ?>
                    No recipes have been saved yet. Try <a href="index.php">All Recipes</a> or save dishes you love from a recipe page.
                  <?php else : ?>
                    No published recipes to show yet.
                  <?php endif; ?>
                </p>
                <?php if (empty($cn_submit_recipe_disabled)) : ?>
                  <a class="btn cn-btn-primary cn-editorial-shadow px-4 py-3" href="create-recipe.php">Submit a recipe</a>
                <?php endif; ?>
              </div>
            </div>
            <?php else : ?>
              <?php foreach ($cn_grid_recipes as $cn_r) : ?>
                <?php
                $cn_href = 'recipe-details.php?slug=' . rawurlencode((string) $cn_r['slug']);
                $cn_img = cn_recipe_resolve_asset_url(isset($cn_r['hero_path']) ? (string) $cn_r['hero_path'] : '');
                $cn_title = (string) $cn_r['title'];
                $cn_desc = isset($cn_r['description']) ? trim((string) $cn_r['description']) : '';
                $cn_desc_short = $cn_desc !== '' ? (mb_strlen($cn_desc) > 160 ? mb_substr($cn_desc, 0, 157) . '…' : $cn_desc) : '';
                $cn_mins = cn_recipe_card_minutes_total($cn_r);
                $cn_time_label = $cn_mins !== null ? strtoupper((string) preg_replace('/\s+/', ' ', cn_recipe_format_minutes($cn_mins))) : null;
                $cn_feat = !empty($cn_r['featured']) && (int) $cn_r['featured'] === 1;
                $cn_cat_lbl = isset($cn_r['category_label']) ? trim((string) $cn_r['category_label']) : '';
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
                  <?php elseif ($cn_cat_lbl !== '') : ?>
                  <div class="position-absolute top-0 start-0 p-3">
                    <span class="cn-chip cn-chip--tertiary" style="background: rgba(114, 70, 0, 0.22); color: #724600"><?php echo htmlspecialchars($cn_cat_lbl, ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="px-1">
                  <!--<div class="d-flex justify-content-between align-items-start gap-2">
                    <h3 class="h5 fw-bold cn-headline mb-1"><?php echo htmlspecialchars($cn_title, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <span class="material-symbols-outlined cn-text-muted" aria-hidden="true">bookmark</span>
                  </div>-->
                  <?php if ($cn_desc_short !== '') : ?>
                  <p class="cn-text-muted mb-2"><?php echo htmlspecialchars($cn_title, ENT_QUOTES, 'UTF-8'); ?></p>
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
            <?php endif; ?>

            <!-- CTA overlap -->
            <div class="col-12 col-lg-8">
              <div class="cn-editorial-shadow p-4 p-md-5 rounded-4 position-relative overflow-hidden" style="background: var(--cn-primary)">
                <div
                  class="position-absolute"
                  style="width: 260px; height: 260px; border-radius: 999px; right: -48px; bottom: -48px; background: var(--cn-primary-container); opacity: 0.25"
                ></div>
                <div class="row align-items-center position-relative">
                  <div class="col-12 col-md">
                    <h3 class="h2 fw-bold cn-headline text-white mb-3">Join the Artisanal Community</h3>
                    <p class="mb-4" style="color: rgba(240, 255, 240, 0.72)">
                      Share your culinary creations, save your favorite recipes, and connect with thousands of food enthusiasts worldwide.
                    </p>
                    <a class="btn px-4 py-3 fw-bold rounded-4" href="dashboard.php" style="background: var(--cn-secondary-container); color: #663100">
                      Get Started Today
                    </a>
                  </div>
                  <div class="col-md-4 d-none d-md-block">
                    <div class="d-grid gap-2" style="grid-template-columns: repeat(2, 1fr)">
                      <div class="rounded-3 overflow-hidden" style="height: 84px">
                        <img
                          class="w-100 h-100 object-fit-cover"
                          alt="community"
                          src="https://lh3.googleusercontent.com/aida-public/AB6AXuCqakM5820nLk23pgpKEt0bMFktmwizW9B5ABxI5h5ZqgmXXTJzc8H0-InNCdjQ6J0-cyprWwCsGpaGuijWBX2H-WTKJ_rcNXnLyHWbDhIsoImpigadlqospUVyL6jzXZjMSkHgYps7TNBZ7UxPbtmR1JdvFLRcZNQ31TGIv1lWVCUoKna1ZEZSjQpbrlaa_NSVTNGeFHq4Nl0QHBhUY0RT-LHGpak_E5M5oyhLZQJP0m1OcYLYlXAzyA3RTmM9WCEq-2QLcRNIqeCB"
                        />
                      </div>
                      <div class="rounded-3 overflow-hidden mt-3" style="height: 84px">
                        <img
                          class="w-100 h-100 object-fit-cover"
                          alt="community"
                          src="https://lh3.googleusercontent.com/aida-public/AB6AXuAu47PXBYcY43v1vt6wK4Inn0_5RKm0IUhujmqZmf7KNhGC60pGX6it_vM0z5fXsNRRO-6DMi3ltQ0tNAMDbfC_0_BGN9UTciqyG7ltCVSI7XCvCseQcdmaZ81B37kJqt_BEv8xG1jf1EuvlXhvZaL_xXloIGR-c49DixUsH_GI7FrAGxdTqgv9yI4swvt6xjMCCxn8oQ1BS1lDy4tlNbAQoJzW_Wznq2VqC4F5Zd6euE44zod6fHomiITEScNrcAwHYnQydsFCu7UC"
                        />
                      </div>
                      <div class="rounded-3 overflow-hidden" style="height: 84px">
                        <img
                          class="w-100 h-100 object-fit-cover"
                          alt="community"
                          src="https://lh3.googleusercontent.com/aida-public/AB6AXuBvwNgFWCmmfZxCOv1ALZJYAecbW52opsErHit_6OsybXHmvqEkfcqOrchSl2KRg-RDWD0hvuBxYcxHjzoafGNAwCpzwwcs6svRnESE6Byy4XMycFOitwaaunmBjLLjchu2iiN07Z9UwopLEgBWoOQAWbRRIKHHlyk8_tuYVB5nnFRvfy06WT4Y9HuhiYEOJYg2jAIo3qUQ-Rslr630bj1J-mFMIkQCk89IwafwWDDvFsQPItS_1Jpg8e0qvHuL7eJYlLlwNGyud5IT"
                        />
                      </div>
                      <div class="rounded-3 overflow-hidden mt-3" style="height: 84px">
                        <img
                          class="w-100 h-100 object-fit-cover"
                          alt="community"
                          src="https://lh3.googleusercontent.com/aida-public/AB6AXuD5o3-h1fm5tTwl8JDDoTlb3rcIzEHduGUENTo-vUTQg7oFbhL-s1MsaGG8IaDKEdaDK_qIrr1BQ4CM3unIBTejsUk-V7bcEbkGfG-WTDKKNzzFaDdO4hMR9XnEmq8RD-UZQYRkkXTbKJ4j3qRodhVqTC-nQCptF_6V7Y7JOonBmKKJtvi4NEc9KoAuJzWdk8RD6QjFcEGj_CXLqhSDiG5KVkytZ47yRtw_Sy6_srj2GJ55J-DdOjkUSCiSrQaAJdoffSrHTwxXSc1s"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Footer -->
          <footer class="rounded-4 p-4 p-md-5 mt-5" style="background: #05220a; color: rgba(240, 255, 240, 0.86)">
            <div class="row g-4">
              <div class="col-12 col-md-4">
                <div class="h4 fw-bold cn-headline text-white mb-3">CookNet</div>
                <p class="mb-3" style="color: rgba(240, 255, 240, 0.6)">
                  Redefining the digital culinary experience through artisanal editorial curation and community-driven excellence.
                </p>
                <div class="d-flex gap-3" style="color: rgba(240, 255, 240, 0.5)">
                  <span class="material-symbols-outlined">public</span>
                  <span class="material-symbols-outlined">share</span>
                  <span class="material-symbols-outlined">mail</span>
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="row g-3">
                  <div class="col-6">
                    <div class="fw-bold text-white mb-2">Platform</div>
                    <div class="d-flex flex-column gap-2 small">
                      <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">About CookNet</a>
                      <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Editorial Policy</a>
                      <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Careers</a>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="fw-bold text-white mb-2">Support</div>
                    <div class="d-flex flex-column gap-2 small">
                      <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Contact Us</a>
                      <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Privacy</a>
                      <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Cookies</a>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="p-4 rounded-4" style="background: rgba(126, 173, 134, 0.18)">
                  <div class="fw-bold text-white mb-2">Stay Inspired</div>
                  <p class="small mb-3" style="color: rgba(240, 255, 240, 0.65)">Join 50k+ food lovers receiving our weekly artisanal newsletter.</p>
                  <div class="d-flex gap-2">
                    <input class="form-control cn-input" type="email" placeholder="Your email" />
                    <button class="btn rounded-4 px-3" style="background: var(--cn-secondary); color: #fff" aria-label="Send">
                      <span class="material-symbols-outlined">send</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="pt-4 mt-4 small text-center" style="border-top: 1px solid rgba(240, 255, 240, 0.12); color: rgba(240, 255, 240, 0.45)">
              © 2024 CookNet. The Artisanal Editorial Experience.
            </div>
          </footer>

          <div class="cn-mobile-nav-spacer d-md-none"></div>
        </main>
      </div>
    </div>

    <!-- Mobile bottom nav -->
    <div class="d-md-none fixed-bottom bg-white shadow-lg">
      <div class="d-flex justify-content-around py-2">
        <a class="text-decoration-none text-center" href="index.php" data-nav style="color: var(--cn-primary)">
          <div><span class="material-symbols-outlined cn-fill">home</span></div>
          <div class="small fw-bold" style="font-size: 0.65rem">Home</div>
        </a>
        <a class="text-decoration-none text-center cn-text-muted" href="index.php" style="color: var(--cn-on-surface-variant)">
          <div><span class="material-symbols-outlined">explore</span></div>
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

