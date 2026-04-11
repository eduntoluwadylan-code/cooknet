<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cn_nav_active = '';
$cn_nav_search = false;
$cn_category_active = null;
$cn_submit_recipe_disabled = false;
$cn_sidebar_sticky_top = '104px';

require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/categories_helper.php';
require_once __DIR__ . '/includes/category_recipes_query.php';

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
        return [['qty' => '', 'name' => ''], ['qty' => '', 'name' => '']];
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

/** @return list<string> */
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

function cn_edit_prep_range_from_minutes(?int $m): string
{
    if ($m === null) {
        return '';
    }

    return match ($m) {
        25 => '15_30',
        45 => '30_60',
        90 => '60_120',
        150 => '120_plus',
        default => '',
    };
}

cn_require_login('index.php?login=1');

$pdo = cn_pdo();
$uid = cn_user_id();
if ($uid === null) {
    cn_redirect('index.php?login=1');
}

$cn_recipe_id = isset($_POST['recipe_id']) ? (int) $_POST['recipe_id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
if ($cn_recipe_id < 1) {
    cn_redirect('dashboard.php');
}

$st = $pdo->prepare(
    'SELECT r.* FROM recipes r WHERE r.id = ? LIMIT 1'
);
$st->execute([$cn_recipe_id]);
$cn_row = $st->fetch(PDO::FETCH_ASSOC);
if (!$cn_row || (int) $cn_row['user_id'] !== $uid) {
    cn_redirect('dashboard.php');
}

$cn_categories = cn_get_categories();
$cn_csrf = cn_csrf_token();
$cn_recipe_error = null;

$st = $pdo->prepare('SELECT quantity, name FROM recipe_ingredients WHERE recipe_id = ? ORDER BY sort_order ASC, id ASC');
$st->execute([$cn_recipe_id]);
$cn_loaded_ings = [];
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $cn_loaded_ings[] = ['qty' => (string) $r['quantity'], 'name' => (string) $r['name']];
}
if (count($cn_loaded_ings) < 2) {
    $cn_loaded_ings = array_pad($cn_loaded_ings, 2, ['qty' => '', 'name' => '']);
}

$st = $pdo->prepare('SELECT instruction FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number ASC, id ASC');
$st->execute([$cn_recipe_id]);
$cn_loaded_steps = array_map(static fn (array $r): string => (string) $r['instruction'], $st->fetchAll(PDO::FETCH_ASSOC));
if (count($cn_loaded_steps) < 2) {
    $cn_loaded_steps = array_pad($cn_loaded_steps, 2, '');
}

$st = $pdo->prepare('SELECT path_or_url FROM recipe_images WHERE recipe_id = ? AND role = \'hero\' ORDER BY sort_order ASC, id ASC LIMIT 1');
$st->execute([$cn_recipe_id]);
$cn_hero_path = $st->fetchColumn();
$cn_hero_preview_url = cn_recipe_resolve_asset_url($cn_hero_path !== false ? (string) $cn_hero_path : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cn_csrf_verify(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        $cn_recipe_error = 'Invalid session. Please refresh the page and try again.';
    } else {
        require_once __DIR__ . '/services/EditRecipeService.php';
        $rid = isset($_POST['recipe_id']) ? (int) $_POST['recipe_id'] : 0;
        if ($rid !== $cn_recipe_id) {
            $cn_recipe_error = 'Invalid recipe.';
        } else {
            try {
                $svc = new EditRecipeService();
                $svc->update($pdo, $uid, $cn_recipe_id, $_POST, $_FILES);
                $st = $pdo->prepare('SELECT slug FROM recipes WHERE id = ? LIMIT 1');
                $st->execute([$cn_recipe_id]);
                $slug = (string) $st->fetchColumn();
                cn_redirect('recipe-details.php?slug=' . rawurlencode($slug));
            } catch (InvalidArgumentException $e) {
                $cn_recipe_error = $e->getMessage();
            } catch (Throwable $e) {
                error_log('[CookNet] edit recipe: ' . $e->getMessage());
                $cn_recipe_error = defined('CN_DEBUG') && CN_DEBUG ? $e->getMessage() : 'Could not save changes.';
            }
        }
    }
}

$cn_use_post = $_SERVER['REQUEST_METHOD'] === 'POST' && $cn_recipe_error !== null;
$cn_form_ingredients = $cn_use_post ? cn_create_recipe_form_ingredients($_POST) : $cn_loaded_ings;
$cn_form_steps = $cn_use_post ? cn_create_recipe_form_steps($_POST) : $cn_loaded_steps;

$cn_prep_range = $cn_use_post
    ? (string) ($_POST['prep_range'] ?? '')
    : cn_edit_prep_range_from_minutes(
        isset($cn_row['prep_minutes']) && $cn_row['prep_minutes'] !== null && $cn_row['prep_minutes'] !== ''
            ? (int) $cn_row['prep_minutes']
            : null
    );

$cn_show_repick_hero = $cn_recipe_error !== null && $_SERVER['REQUEST_METHOD'] === 'POST';
$cn_title_val = $cn_use_post ? (string) ($_POST['title'] ?? '') : (string) $cn_row['title'];
$cn_desc_val = $cn_use_post ? (string) ($_POST['description'] ?? '') : (string) ($cn_row['description'] ?? '');
$cn_cat_val = $cn_use_post ? (int) ($_POST['category_id'] ?? 0) : (int) $cn_row['category_id'];
$cn_cook_val = $cn_use_post
    ? (($_POST['cook_minutes'] ?? '') !== '' ? (int) $_POST['cook_minutes'] : '')
    : (isset($cn_row['cook_minutes']) && $cn_row['cook_minutes'] !== null && $cn_row['cook_minutes'] !== '' ? (int) $cn_row['cook_minutes'] : '');
$cn_diff_val = $cn_use_post ? (string) ($_POST['difficulty'] ?? '') : (string) ($cn_row['difficulty'] ?? '');
?>
<!doctype html>
<html lang="en">
  <head>
    <title>CookNet | Edit Recipe</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link rel="stylesheet" href="assets/css/styles.css" />
  </head>
  <body data-cn-existing-hero="<?php echo htmlspecialchars($cn_hero_preview_url, ENT_QUOTES, 'UTF-8'); ?>">
    <?php require __DIR__ . '/includes/navbar.php'; ?>

    <main class="container-fluid" style="padding-top: 96px">
      <div class="row gx-4">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <section class="col-12 col-lg-9 col-xl-7 px-3 px-md-4">
          <header class="mb-4 mb-md-5">
            <h1 class="display-5 fw-bold cn-headline mb-2" style="color: var(--cn-primary); letter-spacing: -0.02em">Edit Recipe</h1>
            <p class="lead cn-text-muted mb-0" style="max-width: 54ch">Update your recipe details, ingredients, and steps.</p>
          </header>

          <?php if ($cn_recipe_error !== null) : ?>
            <div class="alert alert-danger rounded-4 mb-4" role="alert"><?php echo htmlspecialchars($cn_recipe_error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <form class="pb-5" method="post" action="edit-recipe.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cn_csrf, ENT_QUOTES, 'UTF-8'); ?>" />
            <input type="hidden" name="recipe_id" value="<?php echo (string) $cn_recipe_id; ?>" />

            <div class="cn-card p-4 p-md-5 mb-4">
              <div class="row g-4">
                <div class="col-12">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Recipe Title</label>
                  <input class="form-control cn-input fs-5" name="title" required maxlength="255" value="<?php echo htmlspecialchars($cn_title_val, ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="col-12">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Description</label>
                  <textarea class="form-control cn-input" name="description" rows="3"><?php echo htmlspecialchars($cn_desc_val, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Category</label>
                  <select class="form-select cn-input" name="category_id" required aria-label="Recipe category">
                    <option value="" disabled <?php echo $cn_cat_val < 1 ? 'selected' : ''; ?>>Select a category</option>
                    <?php foreach ($cn_categories as $cn_cat) : ?>
                      <?php $cid = (int) $cn_cat['id']; ?>
                      <option value="<?php echo $cid; ?>" <?php echo $cn_cat_val === $cid ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cn_cat['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Prep Time</label>
                  <select class="form-select cn-input" name="prep_range">
                    <option value="">—</option>
                    <option value="15_30" <?php echo $cn_prep_range === '15_30' ? 'selected' : ''; ?>>15 - 30 minutes</option>
                    <option value="30_60" <?php echo $cn_prep_range === '30_60' ? 'selected' : ''; ?>>30 - 60 minutes</option>
                    <option value="60_120" <?php echo $cn_prep_range === '60_120' ? 'selected' : ''; ?>>1 - 2 hours</option>
                    <option value="120_plus" <?php echo $cn_prep_range === '120_plus' ? 'selected' : ''; ?>>2+ hours</option>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Cook time (optional, minutes)</label>
                  <input class="form-control cn-input" type="number" name="cook_minutes" min="1" max="9999" placeholder="e.g. 90" value="<?php echo $cn_cook_val !== '' ? (string) (int) $cn_cook_val : ''; ?>" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold small text-uppercase" style="color: var(--cn-primary); letter-spacing: 0.12em">Difficulty</label>
                  <select class="form-select cn-input" name="difficulty">
                    <option value="" <?php echo $cn_diff_val === '' ? 'selected' : ''; ?>>—</option>
                    <option value="easy" <?php echo $cn_diff_val === 'easy' ? 'selected' : ''; ?>>Easy</option>
                    <option value="medium" <?php echo $cn_diff_val === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="hard" <?php echo $cn_diff_val === 'hard' ? 'selected' : ''; ?>>Hard</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="cn-card p-4 p-md-5 mb-4 position-relative overflow-hidden">
              <label class="form-label fw-bold small text-uppercase d-block" style="color: var(--cn-primary); letter-spacing: 0.12em" for="cn-hero-file">Hero Image</label>
              <?php if ($cn_show_repick_hero) : ?>
                <div class="small cn-text-muted mb-2">Please choose your hero image again if you are replacing it (browsers cannot restore file fields after an error).</div>
              <?php endif; ?>
              <div class="cn-card--lowest rounded-4 overflow-hidden">
                <div
                  id="cn-hero-preview-wrap"
                  class="position-relative rounded-4 overflow-hidden cn-editorial-shadow mb-3<?php echo ($cn_hero_preview_url === '' || $cn_show_repick_hero) ? ' d-none' : ''; ?>"
                  style="max-height: 320px"
                >
                  <img id="cn-hero-preview-img" src="<?php echo $cn_hero_preview_url !== '' ? htmlspecialchars($cn_hero_preview_url, ENT_QUOTES, 'UTF-8') : ''; ?>" alt="" class="w-100 object-fit-cover" style="max-height: 320px" />
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
                  <input id="cn-hero-file" class="d-none" type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" />
                  <div class="material-symbols-outlined mb-2" style="font-size: 44px; color: var(--cn-primary)">add_a_photo</div>
                  <div class="fw-semibold cn-text-muted">Replace image — drag and drop or <span class="fw-bold" style="color: var(--cn-primary)">browse</span></div>
                  <div class="small cn-text-muted mt-1">JPG, PNG, or WebP — max 5MB. Required to <strong>publish</strong> (or keep your current hero).</div>
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
                  <?php $cn_ing_row_class = 'd-flex gap-3 align-items-center' . ($i % 2 === 1 ? ' p-2 rounded-4 cn-card--lowest' : ''); ?>
                  <div class="<?php echo htmlspecialchars($cn_ing_row_class, ENT_QUOTES, 'UTF-8'); ?>">
                    <input class="form-control cn-input" style="max-width: 110px" name="ingredient_qty[]" type="text" placeholder="Qty" value="<?php echo htmlspecialchars($cn_ing['qty'], ENT_QUOTES, 'UTF-8'); ?>" />
                    <input class="form-control cn-input" name="ingredient_name[]" type="text" placeholder="Ingredient" value="<?php echo htmlspecialchars($cn_ing['name'], ENT_QUOTES, 'UTF-8'); ?>" />
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
                  ?>
                  <div class="d-flex gap-4">
                    <div class="flex-shrink-0">
                      <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold cn-headline" style="width: 44px; height: 44px; background: <?php echo htmlspecialchars($cn_step_bg, ENT_QUOTES, 'UTF-8'); ?>; color: <?php echo htmlspecialchars($cn_step_fg, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($cn_step_num, ENT_QUOTES, 'UTF-8'); ?>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <textarea class="form-control cn-input" name="step_instructions[]" rows="2"><?php echo htmlspecialchars($cn_step_text, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-3 pt-3">
              <button class="btn cn-btn-primary py-3 py-md-4 flex-grow-1 cn-editorial-shadow" type="submit" name="recipe_action" value="published" style="font-size: 1.05rem">Publish Recipe</button>
              <button class="btn cn-btn-secondary py-3 py-md-4 px-4" type="submit" name="recipe_action" value="draft" style="font-size: 1.05rem">Save as Draft</button>
              <a class="btn btn-outline-secondary py-3 py-md-4 px-4" style="font-size: 1.05rem" href="recipe-details.php?slug=<?php echo rawurlencode((string) $cn_row['slug']); ?>">Cancel</a>
            </div>
          </form>
        </section>

        <aside class="col-xl-3 d-none d-xl-block">
          <div class="position-sticky" style="top: 104px">
            <div class="p-4 rounded-4" style="background: rgba(21, 66, 18, 0.08)">
              <div class="fw-bold cn-headline mb-2" style="color: var(--cn-primary)">Editing</div>
              <p class="small cn-text-muted mb-0">Changes apply to this recipe only. Slug may update if the title changes.</p>
            </div>
          </div>
        </aside>
      </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
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
        const existing = document.body.getAttribute("data-cn-existing-hero");

        function revoke() {
          if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
          }
        }

        function showPreview(file) {
          revoke();
          if (!file || !file.type.startsWith("image/")) {
            if (existing) {
              previewImg.src = existing;
              previewWrap.classList.remove("d-none");
              fileNameEl.textContent = "Current hero image";
              return;
            }
            previewWrap.classList.add("d-none");
            previewImg.removeAttribute("src");
            fileNameEl.textContent = "";
            return;
          }
          objectUrl = URL.createObjectURL(file);
          previewImg.src = objectUrl;
          fileNameEl.textContent = file.name;
          previewWrap.classList.remove("d-none");
        }

        input?.addEventListener("change", () => {
          const file = input.files && input.files[0];
          showPreview(file || null);
        });

        clearBtn?.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          input.value = "";
          revoke();
          if (existing) {
            previewImg.src = existing;
            previewWrap.classList.remove("d-none");
            fileNameEl.textContent = "Current hero image";
          } else {
            previewWrap.classList.add("d-none");
            previewImg.removeAttribute("src");
            fileNameEl.textContent = "";
          }
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
