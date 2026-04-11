<?php
$cn_nav_active = '';
$cn_nav_search = false;
$cn_category_active = null;
$cn_submit_recipe_disabled = false;
$cn_sidebar_sticky_top = '104px';

require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/categories_helper.php';

/**
 * @return list<array{qty: string, name: string}>
 */
function cn_create_recipe_form_ingredients(array $post): array
{
    $qty = $post['ingredient_qty'] ?? null;
    $name = $post['ingredient_name'] ?? null;
    if (!is_array($qty)) {
        $qty = [];
    }
    if (!is_array($name)) {
        $name = [];
    }
    if (count($qty) === 0 && count($name) === 0) {
        return [
            ['qty' => '', 'name' => ''],
            ['qty' => '', 'name' => ''],
        ];
    }
    $n = max(count($qty), count($name));
    $out = [];
    for ($i = 0; $i < $n; $i++) {
        $out[] = [
            'qty' => isset($qty[$i]) ? (string) $qty[$i] : '',
            'name' => isset($name[$i]) ? (string) $name[$i] : '',
        ];
    }
    return $out;
}

/**
 * @return list<string>
 */
function cn_create_recipe_form_steps(array $post): array
{
    $raw = $post['step_instructions'] ?? null;
    if (!is_array($raw)) {
        return ['', ''];
    }
    $steps = [];
    foreach ($raw as $line) {
        $steps[] = (string) $line;
    }
    if (count($steps) === 0) {
        return ['', ''];
    }
    if (count($steps) < 2) {
        $steps = array_pad($steps, 2, '');
    }
    return $steps;
}

cn_require_login('index.php?login=1');

$pdo = cn_pdo();
$cn_categories = cn_get_categories();
$cn_csrf = cn_csrf_token();
$cn_recipe_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cn_csrf_verify(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        $cn_recipe_error = 'Invalid session. Please refresh the page and try again.';
    } else {
        require_once __DIR__ . '/services/CreateRecipeService.php';
        $uid = cn_user_id();
        if ($uid === null) {
            cn_redirect('index.php?login=1');
        }
        $svc = new CreateRecipeService();
        try {
            $recipeId = $svc->create($pdo, $uid, $_POST, $_FILES);
            $st = $pdo->prepare('SELECT slug FROM recipes WHERE id = ? LIMIT 1');
            $st->execute([$recipeId]);
            $slug = (string) $st->fetchColumn();
            cn_redirect('recipe-details.php?slug=' . rawurlencode($slug));
        } catch (InvalidArgumentException $e) {
            $cn_recipe_error = $e->getMessage();
        } catch (Throwable $e) {
            error_log(
                '[CookNet] create recipe failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n"
                . $e->getTraceAsString()
            );
            if (defined('CN_DEBUG') && CN_DEBUG) {
                $detail = $e->getMessage();
                $prev = $e->getPrevious();
                if ($prev instanceof Throwable) {
                    $detail .= ' — ' . $prev->getMessage();
                }
                $detail .= ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']';
                $cn_recipe_error = $detail;
            } else {
                $cn_recipe_error = 'Could not save your recipe. Check the PHP/Apache error log on the server, or temporarily enable details: remove or rename `includes/production.mode` if present, and ensure `CN_DEBUG` is enabled in `includes/config.php`.';
            }
        }
    }
}

$cn_form_ingredients = cn_create_recipe_form_ingredients($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : []);
$cn_form_steps = cn_create_recipe_form_steps($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : []);
$cn_show_repick_hero = $cn_recipe_error !== null && $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!doctype html>
<html lang="en">
  <head>
    <title>CookNet | Create Recipe</title>
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

    <main class="container-fluid" style="padding-top: 96px">
      <div class="row gx-4">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <section class="col-12 col-lg-9 col-xl-7 px-3 px-md-4">
          <header class="mb-4 mb-md-5">
            <h1 class="display-5 fw-bold cn-headline mb-2" style="color: var(--cn-primary); letter-spacing: -0.02em">Create Recipe</h1>
            <p class="lead cn-text-muted mb-0" style="max-width: 54ch">
              Share your culinary masterpiece with the CookNet community. Every detail matters in the artisanal experience.
            </p>
          </header>

          <?php if ($cn_recipe_error !== null) : ?>
            <div class="alert alert-danger rounded-4 mb-4" role="alert"><?php echo htmlspecialchars($cn_recipe_error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <form class="pb-5" method="post" action="create-recipe.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cn_csrf, ENT_QUOTES, 'UTF-8'); ?>" />

            <div class="cn-card p-4 p-md-5 mb-4">
              <div class="row g-4">
                <div class="col-12">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Recipe Title</label>
                  <input class="form-control cn-input fs-5" name="title" required maxlength="255" placeholder="e.g. Herb-Crusted Roasted Sea Bass" value="<?php echo isset($_POST['title']) ? htmlspecialchars((string) $_POST['title'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                </div>
                <div class="col-12">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Description</label>
                  <textarea class="form-control cn-input" name="description" rows="3" placeholder="Tell the story behind this dish..."><?php echo isset($_POST['description']) ? htmlspecialchars((string) $_POST['description'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Category</label>
                  <select class="form-select cn-input" name="category_id" required aria-label="Recipe category">
                    <option value="" disabled <?php echo empty($_POST['category_id']) ? 'selected' : ''; ?>>Select a category</option>
                    <?php foreach ($cn_categories as $cn_cat) : ?>
                      <?php $cid = (int) $cn_cat['id']; ?>
                      <option value="<?php echo $cid; ?>" <?php echo (isset($_POST['category_id']) && (int) $_POST['category_id'] === $cid) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cn_cat['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Prep Time</label>
                  <select class="form-select cn-input" name="prep_range">
                    <option value="">—</option>
                    <option value="15_30" <?php echo (isset($_POST['prep_range']) && $_POST['prep_range'] === '15_30') ? 'selected' : ''; ?>>15 - 30 minutes</option>
                    <option value="30_60" <?php echo (isset($_POST['prep_range']) && $_POST['prep_range'] === '30_60') ? 'selected' : ''; ?>>30 - 60 minutes</option>
                    <option value="60_120" <?php echo (isset($_POST['prep_range']) && $_POST['prep_range'] === '60_120') ? 'selected' : ''; ?>>1 - 2 hours</option>
                    <option value="120_plus" <?php echo (isset($_POST['prep_range']) && $_POST['prep_range'] === '120_plus') ? 'selected' : ''; ?>>2+ hours</option>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Cook time (optional, minutes)</label>
                  <input class="form-control cn-input" type="number" name="cook_minutes" min="1" max="9999" placeholder="e.g. 90" value="<?php echo (isset($_POST['cook_minutes']) && $_POST['cook_minutes'] !== '') ? (int) $_POST['cook_minutes'] : ''; ?>" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Difficulty</label>
                  <select class="form-select cn-input" name="difficulty">
                    <option value="" <?php echo empty($_POST['difficulty']) ? 'selected' : ''; ?>>—</option>
                    <option value="easy" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'easy') ? 'selected' : ''; ?>>Easy</option>
                    <option value="medium" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                    <option value="hard" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'hard') ? 'selected' : ''; ?>>Hard</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="cn-card p-4 p-md-5 mb-4 position-relative overflow-hidden">
              <label class="form-label fw-bold small text-uppercase d-block" style="color: var(--cn-primary); letter-spacing: 0.12em" for="cn-hero-file">Hero Image</label>
              <?php if ($cn_show_repick_hero) : ?>
                <div class="small cn-text-muted mb-2">Please choose your hero image again (browsers cannot restore file fields after an error).</div>
              <?php endif; ?>
              <div class="cn-card--lowest rounded-4 overflow-hidden">
                <div
                  id="cn-hero-preview-wrap"
                  class="d-none position-relative rounded-4 overflow-hidden cn-editorial-shadow mb-3"
                  style="max-height: 320px"
                >
                  <img id="cn-hero-preview-img" src="" alt="" class="w-100 object-fit-cover" style="max-height: 320px" />
                  <button
                    type="button"
                    id="cn-hero-preview-clear"
                    class="btn btn-sm position-absolute top-0 end-0 m-2 rounded-pill cn-editorial-shadow"
                    style="background: rgba(255,255,255,0.92); color: var(--cn-primary); font-weight: 700"
                  >
                    Remove
                  </button>
                </div>
                <label
                  class="rounded-4 p-5 text-center cn-card--lowest d-block mb-0"
                  style="border: 2px dashed rgba(194, 201, 187, 0.45); cursor: pointer"
                  for="cn-hero-file"
                  id="cn-hero-dropzone"
                >
                  <input
                    id="cn-hero-file"
                    class="d-none"
                    type="file"
                    name="hero_image"
                    accept="image/jpeg,image/png,image/webp"
                  />
                  <div class="material-symbols-outlined mb-2" style="font-size: 44px; color: var(--cn-primary)">add_a_photo</div>
                  <div class="fw-semibold cn-text-muted">Drag and drop or <span class="fw-bold" style="color: var(--cn-primary)">browse</span></div>
                  <div class="small cn-text-muted mt-1">JPG, PNG, or WebP — max 5MB. Required to <strong>publish</strong>; optional for drafts.</div>
                  <div id="cn-hero-file-name" class="small fw-semibold mt-2 cn-text-muted"></div>
                </label>
              </div>
            </div>

            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-end mb-2">
                <label class="form-label fw-bold small text-uppercase mb-0" style="color: var(--cn-primary); letter-spacing: 0.12em">Ingredients</label>
                <button class="btn btn-link text-decoration-none fw-bold p-0" type="button" data-add-ingredient style="color: var(--cn-primary)">
                  <span class="material-symbols-outlined align-middle">add_circle</span> Add Row
                </button>
              </div>

              <div class="d-grid gap-3" data-ingredients>
                <?php foreach ($cn_form_ingredients as $i => $cn_ing) : ?>
                  <?php
                  $cn_ing_row_class = 'd-flex gap-3 align-items-center' . ($i % 2 === 1 ? ' p-2 rounded-4 cn-card--lowest' : '');
                  ?>
                  <div class="<?php echo htmlspecialchars($cn_ing_row_class, ENT_QUOTES, 'UTF-8'); ?>">
                    <input class="form-control cn-input" style="max-width: 110px" name="ingredient_qty[]" type="text" placeholder="Qty" value="<?php echo htmlspecialchars($cn_ing['qty'], ENT_QUOTES, 'UTF-8'); ?>" />
                    <input class="form-control cn-input" name="ingredient_name[]" type="text" placeholder="e.g. Organic Extra Virgin Olive Oil" value="<?php echo htmlspecialchars($cn_ing['name'], ENT_QUOTES, 'UTF-8'); ?>" />
                    <button class="btn btn-link text-decoration-none text-danger p-2" type="button" aria-label="Remove ingredient">
                      <span class="material-symbols-outlined">delete</span>
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-end mb-2">
                <label class="form-label fw-bold small text-uppercase mb-0" style="color: var(--cn-primary); letter-spacing: 0.12em">Instructions</label>
                <button class="btn btn-link text-decoration-none fw-bold p-0" type="button" data-add-step style="color: var(--cn-primary)">
                  <span class="material-symbols-outlined align-middle">add_task</span> Add Step
                </button>
              </div>

              <div class="d-grid gap-4" data-steps>
                <?php foreach ($cn_form_steps as $i => $cn_step_text) : ?>
                  <?php
                  $cn_step_num = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                  $cn_step_bg = $i % 2 === 0 ? 'rgba(21, 66, 18, 0.12)' : 'var(--cn-surface-container-high)';
                  $cn_step_fg = $i % 2 === 0 ? 'var(--cn-primary)' : '#6b7280';
                  $cn_ph = $i % 2 === 0 ? 'Start by preparing your workspace...' : 'Heat your skillet over a medium flame...';
                  ?>
                  <div class="d-flex gap-4">
                    <div class="flex-shrink-0">
                      <div
                        class="d-flex align-items-center justify-content-center rounded-circle fw-bold cn-headline"
                        style="width: 44px; height: 44px; background: <?php echo htmlspecialchars($cn_step_bg, ENT_QUOTES, 'UTF-8'); ?>; color: <?php echo htmlspecialchars($cn_step_fg, ENT_QUOTES, 'UTF-8'); ?>"
                      >
                        <?php echo htmlspecialchars($cn_step_num, ENT_QUOTES, 'UTF-8'); ?>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <textarea class="form-control cn-input" name="step_instructions[]" rows="2" placeholder="<?php echo htmlspecialchars($cn_ph, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cn_step_text, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-3 pt-3">
              <button class="btn cn-btn-primary py-3 py-md-4 flex-grow-1 cn-editorial-shadow" type="submit" name="recipe_action" value="published" style="font-size: 1.05rem">
                Publish Recipe
              </button>
              <button class="btn cn-btn-secondary py-3 py-md-4 px-4" type="submit" name="recipe_action" value="draft" style="font-size: 1.05rem">Save as Draft</button>
            </div>
          </form>
        </section>

        <!-- Right rail -->
        <aside class="col-xl-3 d-none d-xl-block">
          <div class="position-sticky" style="top: 104px">
            <div class="p-4 rounded-4 mb-3" style="background: rgba(252, 143, 52, 0.12)">
              <div class="fw-bold cn-headline mb-2" style="color: var(--cn-secondary)">Editorial Tip</div>
              <p class="small cn-text-muted mb-3">
                Great recipes start with a story. Mention where the inspiration came from or a specific seasonal ingredient that makes it shine.
              </p>
              <div class="rounded-4 overflow-hidden" style="height: 140px">
                <img
                  class="w-100 h-100 object-fit-cover"
                  alt="Fresh ingredients"
                  src="https://lh3.googleusercontent.com/aida-public/AB6AXuBviEYOeMhvOE5zVekbgWlpzFrQe476b01bROZtEcWcXP2oKlo18P8C0EOWcaCfXfYHaffo42DWG4Iy9N8nGUbbK9N0BFkYknMkTWi_syz-UdqG8xQUF_Om_auhCPLG-oq2YprgLJswagQVp0uh1fbN5kSIVw_uhhhz9NQZv9y9teSiLMwXawNmSKCQc-Ucttu41Ic9Ojf0TuzeOY14UPY9T2KuzA_LYcvgOcfGnWCmGysrjkWQYWT6piGaK-Sugo4kg11vzvwXddSy"
                />
              </div>
            </div>

            <div class="p-4 rounded-4" style="background: rgba(21, 66, 18, 0.08)">
              <div class="fw-bold cn-headline mb-3" style="color: var(--cn-primary)">Community Standards</div>
              <ul class="list-unstyled mb-0 d-grid gap-3 small cn-text-muted">
                <li class="d-flex gap-2 align-items-start">
                  <span class="material-symbols-outlined" style="color: var(--cn-primary); font-size: 18px">check_circle</span> High-quality original photos
                </li>
                <li class="d-flex gap-2 align-items-start">
                  <span class="material-symbols-outlined" style="color: var(--cn-primary); font-size: 18px">check_circle</span> Clear metric or imperial units
                </li>
                <li class="d-flex gap-2 align-items-start">
                  <span class="material-symbols-outlined" style="color: var(--cn-primary); font-size: 18px">check_circle</span> Safety warnings for equipment
                </li>
              </ul>
            </div>
          </div>
        </aside>
      </div>
    </main>

    <footer class="container-fluid mt-5 pb-4">
      <div class="rounded-4 p-4" style="background: #05220a; color: rgba(240, 255, 240, 0.86)">
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
            <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Privacy</a>
            <a class="text-decoration-none" href="#" style="color: rgba(240, 255, 240, 0.65)">Careers</a>
          </div>
        </div>
      </div>
    </footer>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>
    <script src="assets/js/app.js"></script>
    <script>
      document.querySelectorAll("[data-ingredients] button").forEach((btn) => {
        btn.addEventListener("click", () => btn.closest(".d-flex")?.remove());
      });

      (function () {
        const input = document.getElementById("cn-hero-file");
        const previewWrap = document.getElementById("cn-hero-preview-wrap");
        const previewImg = document.getElementById("cn-hero-preview-img");
        const fileNameEl = document.getElementById("cn-hero-file-name");
        const clearBtn = document.getElementById("cn-hero-preview-clear");
        const dropzone = document.getElementById("cn-hero-dropzone");
        let objectUrl = null;

        function revoke() {
          if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
          }
        }

        function showPreview(file) {
          revoke();
          if (!file || !file.type.startsWith("image/")) {
            previewWrap.classList.add("d-none");
            previewImg.removeAttribute("src");
            fileNameEl.textContent = "";
            return;
          }
          objectUrl = URL.createObjectURL(file);
          previewImg.src = objectUrl;
          previewImg.alt = file.name || "Hero preview";
          fileNameEl.textContent = file.name;
          previewWrap.classList.remove("d-none");
        }

        input?.addEventListener("change", () => {
          const file = input.files && input.files[0];
          showPreview(file);
        });

        clearBtn?.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          input.value = "";
          showPreview(null);
        });

        ["dragenter", "dragover"].forEach((ev) => {
          dropzone?.addEventListener(ev, (e) => {
            e.preventDefault();
            e.stopPropagation();
          });
        });
        dropzone?.addEventListener("drop", (e) => {
          e.preventDefault();
          e.stopPropagation();
          const f = e.dataTransfer?.files?.[0];
          if (!f || !f.type.startsWith("image/")) return;
          try {
            const dt = new DataTransfer();
            dt.items.add(f);
            input.files = dt.files;
          } catch {
            return;
          }
          showPreview(f);
        });
      })();
    </script>
  </body>
</html>
