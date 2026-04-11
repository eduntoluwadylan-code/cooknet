<?php
/** Navbar search + dropdown (included when $cn_nav_search is set). */
?>
<div class="position-relative cn-nav-search-wrap w-100">
  <div class="input-group rounded-pill overflow-hidden" style="background: var(--cn-surface-container-highest)">
    <span class="input-group-text bg-transparent border-0">
      <span class="material-symbols-outlined" style="color: var(--cn-on-surface-variant)">search</span>
    </span>
    <input
      class="form-control bg-transparent border-0 cn-nav-search-input"
      type="search"
      name="q"
      autocomplete="off"
      placeholder="Search artisanal recipes..."
      aria-label="Search recipes"
      aria-autocomplete="list"
    />
  </div>
  <div
    class="cn-nav-search-results list-group position-absolute start-0 end-0 shadow rounded-3 mt-1 py-0 bg-white"
    style="display: none; z-index: 1060; max-height: 280px; overflow-y: auto; top: 100%"
    role="listbox"
    hidden
  ></div>
</div>
