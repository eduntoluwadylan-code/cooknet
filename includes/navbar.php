<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$cn_me = cn_current_user();
$cn_csrf = cn_csrf_token();
$cn_auth_error = cn_flash_get('auth_error');

if (!function_exists('cn_nav_link_attrs')) {
    function cn_nav_link_attrs(string $active, string $match, string $href, string $label): string
    {
        $isActive = $active !== '' && $active === $match;
        $class = $isActive ? 'nav-link fw-semibold' : 'nav-link fw-semibold cn-text-muted';
        $style = $isActive ? ' style="color: var(--cn-primary)"' : '';
        return '<a class="' . htmlspecialchars($class) . '" data-nav href="' . htmlspecialchars($href) . '"' . $style . '>' . htmlspecialchars($label) . '</a>';
    }
}

$cn_default_next = 'dashboard.php';
$cn_req_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$cn_req_base = $cn_req_path ? basename($cn_req_path) : 'index.php';
if (is_string($cn_req_base) && preg_match('/^[a-zA-Z0-9._-]+\.php$/', $cn_req_base)) {
    $cn_q = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
    $cn_auth_next = $cn_req_base . ($cn_q ? '?' . $cn_q : '');
} else {
    $cn_auth_next = $cn_default_next;
}
if ($cn_req_base === 'index.php') {
    $cn_auth_next = $cn_default_next;
}
?>
<nav class="navbar navbar-expand-lg fixed-top cn-topnav shadow-sm">
  <div class="container-fluid px-3 px-md-4">
    <a class="navbar-brand fw-bold cn-headline" href="index.php" style="color: var(--cn-primary)">CookNet</a>
    <button
      class="navbar-toggler border-0"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#topnav"
      aria-controls="topnav"
      aria-expanded="false"
      aria-label="Toggle navigation"
    >
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topnav">
      <?php if (!empty($cn_nav_search)) : ?>
        <div class="d-lg-none px-2 py-2 w-100">
          <?php require __DIR__ . '/partials/nav_search.php'; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($cn_nav_search)) : ?>
        <div class="d-none d-lg-flex ms-lg-3 me-lg-auto" style="min-width: 360px; max-width: 520px; width: 100%">
          <?php require __DIR__ . '/partials/nav_search.php'; ?>
        </div>
      <?php endif; ?>

      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
        <li class="nav-item"><?php echo cn_nav_link_attrs($cn_nav_active, 'browse', 'index.php', 'Browse'); ?></li>
        <!--<li class="nav-item"><?php echo cn_nav_link_attrs($cn_nav_active, 'community', 'dashboard.php', 'Community'); ?></li>-->
        <li class="nav-item d-flex align-items-center gap-2 ms-lg-2 flex-wrap justify-content-end">
          <a href="favorites.php" class="btn btn-link text-decoration-none p-2"  aria-label="Favorites">
            <span class="material-symbols-outlined" style="color: var(--cn-on-surface-variant)">favorite</span>
          </a>
          <?php if ($cn_me) : ?>
            <a class="text-decoration-none d-flex align-items-center gap-2" href="dashboard.php" title="Dashboard">
              <?php
              $cn_av = !empty($cn_me['avatar_url']) ? (string) $cn_me['avatar_url'] : 'https://ui-avatars.com/api/?name=' . rawurlencode((string) $cn_me['display_name']) . '&background=154212&color=fff';
              ?>
              <img
                src="<?php echo htmlspecialchars($cn_av, ENT_QUOTES, 'UTF-8'); ?>"
                alt=""
                class="rounded-circle object-fit-cover"
                style="width: 40px; height: 40px; border: 2px solid rgba(161, 212, 148, 0.9)"
                width="40"
                height="40"
              />
              <span class="small fw-semibold d-none d-xl-inline cn-headline" style="color: var(--cn-primary); max-width: 8rem" title="<?php echo htmlspecialchars((string) $cn_me['display_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $cn_me['display_name'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <form method="post" action="auth/logout.php" class="d-inline">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cn_csrf, ENT_QUOTES, 'UTF-8'); ?>" />
              <button type="submit" class="btn btn-sm cn-btn-secondary py-2 px-3">Log out</button>
            </form>
          <?php else : ?>
            <button type="button" class="btn btn-sm cn-btn-secondary py-2 px-3" data-bs-toggle="modal" data-bs-target="#cnModalSignIn">Sign in</button>
            <button type="button" class="btn btn-sm cn-btn-primary py-2 px-3" data-bs-toggle="modal" data-bs-target="#cnModalSignUp">Sign up</button>
          <?php endif; ?>
        </li>
      </ul>

      <div class="d-lg-none border-top pt-3 mt-3" style="border-color: rgba(194, 201, 187, 0.35) !important">
        <div class="px-2 pb-1">
          <div class="fw-bold cn-headline" style="color: var(--cn-primary); font-size: 1.05rem">Categories</div>
          <div class="small cn-text-muted mb-2">Artisanal Collections</div>
          <div class="list-group list-group-flush">
            <?php include __DIR__ . '/category_links.php'; ?>
          </div>
          <?php if (empty($cn_submit_recipe_disabled)) : ?>
            <a class="btn cn-btn-primary w-100 py-3 mt-3 cn-editorial-shadow" href="create-recipe.php">Submit Recipe</a>
          <?php else : ?>
            <span class="btn cn-btn-primary w-100 py-3 mt-3 cn-editorial-shadow disabled opacity-75" aria-disabled="true">
              <span class="material-symbols-outlined align-middle me-1" style="font-size: 18px">add</span> Submit Recipe
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</nav>

<?php if (isset($_GET['logged_out']) && !$cn_me) : ?>
  <div class="alert alert-success py-2 px-3 rounded-0 border-0 text-center small mb-0" role="alert" style="position: fixed; top: 76px; left: 0; right: 0; z-index: 1030">
    You have been signed out.
  </div>
<?php endif; ?>

<?php if (!$cn_me) : ?>
<!-- Sign in -->
<div class="modal fade" id="cnModalSignIn" tabindex="-1" aria-labelledby="cnModalSignInLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content cn-card border-0 rounded-4 cn-editorial-shadow">
      <div class="modal-header border-0 pb-0">
        <h2 class="modal-title h4 fw-bold cn-headline" id="cnModalSignInLabel" style="color: var(--cn-primary)">Sign in</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2">
        <?php if ($cn_auth_error && empty($_GET['register'])) : ?>
          <div class="alert alert-danger rounded-4 small mb-3" role="alert"><?php echo htmlspecialchars($cn_auth_error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="post" action="auth/login.php" class="d-grid gap-3">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cn_csrf, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="next" value="<?php echo htmlspecialchars($cn_auth_next, ENT_QUOTES, 'UTF-8'); ?>" />
          <div>
            <label class="form-label fw-bold small text-uppercase mb-2" style="color: var(--cn-primary); letter-spacing: 0.12em">Email</label>
            <input class="form-control cn-input" type="email" name="email" required autocomplete="email" placeholder="you@example.com" />
          </div>
          <div>
            <label class="form-label fw-bold small text-uppercase mb-2" style="color: var(--cn-primary); letter-spacing: 0.12em">Password</label>
            <input class="form-control cn-input" type="password" name="password" required autocomplete="current-password" />
          </div>
          <button type="submit" class="btn cn-btn-primary w-100 py-3 cn-editorial-shadow">Sign in</button>
          <p class="small cn-text-muted mb-0 text-center">
            No account?
            <button type="button" class="btn btn-link p-0 fw-bold text-decoration-none" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#cnModalSignUp" style="color: var(--cn-primary)">Create one</button>
          </p>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Sign up -->
<div class="modal fade" id="cnModalSignUp" tabindex="-1" aria-labelledby="cnModalSignUpLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content cn-card border-0 rounded-4 cn-editorial-shadow">
      <div class="modal-header border-0 pb-0">
        <h2 class="modal-title h4 fw-bold cn-headline" id="cnModalSignUpLabel" style="color: var(--cn-primary)">Create account</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2">
        <?php if ($cn_auth_error && isset($_GET['register'])) : ?>
          <div class="alert alert-danger rounded-4 small mb-3" role="alert"><?php echo htmlspecialchars($cn_auth_error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="post" action="auth/register.php" class="d-grid gap-3">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cn_csrf, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="next" value="<?php echo htmlspecialchars($cn_auth_next, ENT_QUOTES, 'UTF-8'); ?>" />
          <div>
            <label class="form-label fw-bold small text-uppercase mb-2" style="color: var(--cn-primary); letter-spacing: 0.12em">Display name</label>
            <input class="form-control cn-input" type="text" name="display_name" required maxlength="120" autocomplete="name" placeholder="Chef Elena" />
          </div>
          <div>
            <label class="form-label fw-bold small text-uppercase mb-2" style="color: var(--cn-primary); letter-spacing: 0.12em">Email</label>
            <input class="form-control cn-input" type="email" name="email" required autocomplete="email" placeholder="you@example.com" />
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold small text-uppercase mb-2" style="color: var(--cn-primary); letter-spacing: 0.12em">Password</label>
              <input class="form-control cn-input" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="8+ characters" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small text-uppercase mb-2" style="color: var(--cn-primary); letter-spacing: 0.12em">Confirm</label>
              <input class="form-control cn-input" type="password" name="password_confirm" required minlength="8" autocomplete="new-password" />
            </div>
          </div>
          <button type="submit" class="btn cn-btn-primary w-100 py-3 cn-editorial-shadow">Create account</button>
          <p class="small cn-text-muted mb-0 text-center">
            Already have an account?
            <button type="button" class="btn btn-link p-0 fw-bold text-decoration-none" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#cnModalSignIn" style="color: var(--cn-primary)">Sign in</button>
          </p>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
