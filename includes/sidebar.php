<?php
require_once __DIR__ . '/config.php';
$top = preg_match('/^\d+px$/', (string) $cn_sidebar_sticky_top) ? $cn_sidebar_sticky_top : '96px';
?>
<aside class="col-lg-3 col-xl-2 d-none d-lg-block">
  <div class="cn-sidebar p-4 position-sticky" style="top: <?php echo htmlspecialchars($top); ?>; height: calc(100vh - <?php echo htmlspecialchars($top); ?>)">
    <div class="mb-4">
      <div class="fw-bold cn-headline" style="color: var(--cn-primary); font-size: 1.15rem">Categories</div>
      <div class="small cn-text-muted">Artisanal Collections</div>
    </div>

    <div class="list-group list-group-flush">
      <?php include __DIR__ . '/category_links.php'; ?>
    </div>

    <div class="mt-4 pt-3">
      <?php if (empty($cn_submit_recipe_disabled)) : ?>
        <a class="btn cn-btn-primary w-100 py-3 cn-editorial-shadow" href="create-recipe.php">Submit Recipe</a>
      <?php else : ?>
        <span class="btn cn-btn-primary w-100 py-3 cn-editorial-shadow disabled opacity-75" aria-disabled="true">
          <span class="material-symbols-outlined align-middle me-1" style="font-size: 18px">add</span> Submit Recipe
        </span>
      <?php endif; ?>
    </div>
  </div>
</aside>
