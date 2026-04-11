function $(sel, root = document) {
  return root.querySelector(sel);
}

function $all(sel, root = document) {
  return Array.from(root.querySelectorAll(sel));
}

function createEl(tag, attrs = {}, children = []) {
  const el = document.createElement(tag);
  Object.entries(attrs).forEach(([k, v]) => {
    if (k === "class") el.className = v;
    else if (k === "dataset") Object.entries(v).forEach(([dk, dv]) => (el.dataset[dk] = dv));
    else if (k in el) el[k] = v;
    else el.setAttribute(k, v);
  });
  for (const child of children) el.append(child);
  return el;
}

function initActiveNav() {
  const raw = location.pathname.split("/").pop() || "index.php";
  const path = raw.split("?")[0].toLowerCase();
  $all("[data-nav]").forEach((a) => {
    if (!a.getAttribute("href")) return;
    const href = (a.getAttribute("href") || "").split("?")[0].toLowerCase();
    const isActive = href === path;
    a.classList.toggle("active", isActive);
    if (isActive) a.setAttribute("aria-current", "page");
  });
}

function initCreateRecipeForm() {
  const ingredientsWrap = $("[data-ingredients]");
  const stepsWrap = $("[data-steps]");
  if (!ingredientsWrap && !stepsWrap) return;

  const addIngredientBtn = $("[data-add-ingredient]");
  const addStepBtn = $("[data-add-step]");

  function ingredientRowTemplate({ zebra = false } = {}) {
    const row = createEl(
      "div",
      { class: `d-flex gap-3 align-items-center p-2 rounded-4 ${zebra ? "bg-white" : ""}`.trim() },
      [
        createEl("input", {
          class: "form-control cn-input",
          name: "ingredient_qty[]",
          placeholder: "Qty",
          style: "max-width:110px",
          type: "text",
        }),
        createEl("input", {
          class: "form-control cn-input",
          name: "ingredient_name[]",
          placeholder: "e.g. Organic Extra Virgin Olive Oil",
          type: "text",
        }),
        createEl(
          "button",
          { class: "btn btn-link text-decoration-none text-danger p-2", type: "button" },
          [createEl("span", { class: "material-symbols-outlined", textContent: "delete" })]
        ),
      ]
    );

    $("button", row).addEventListener("click", () => row.remove());
    return row;
  }

  function stepTemplate(index) {
    const num = String(index).padStart(2, "0");
    const wrap = createEl("div", { class: "d-flex gap-4" }, [
      createEl("div", { class: "flex-shrink-0" }, [
        createEl("div", { class: "rounded-circle d-flex align-items-center justify-content-center fw-bold cn-headline" }, [
          createEl("div", {
            class: "d-flex align-items-center justify-content-center rounded-circle",
            style: "width:44px;height:44px;background:rgba(21,66,18,0.12);color:var(--cn-primary);",
            textContent: num,
          }),
        ]),
      ]),
      createEl("div", { class: "flex-grow-1" }, [
        createEl("textarea", {
          class: "form-control cn-input",
          rows: 2,
          placeholder: "Describe this step...",
        }),
      ]),
    ]);
    return wrap;
  }

  function renumberSteps() {
    if (!stepsWrap) return;
    const items = $all("[data-step]", stepsWrap);
    items.forEach((item, i) => {
      const badge = $(".cn-step-badge", item);
      if (badge) badge.textContent = String(i + 1).padStart(2, "0");
    });
  }

  if (addIngredientBtn && ingredientsWrap) {
    addIngredientBtn.addEventListener("click", () => {
      const zebra = ingredientsWrap.children.length % 2 === 1;
      ingredientsWrap.append(ingredientRowTemplate({ zebra }));
    });
  }

  if (addStepBtn && stepsWrap) {
    addStepBtn.addEventListener("click", () => {
      const i = stepsWrap.children.length + 1;
      const step = createEl("div", { class: "position-relative", dataset: { step: "1" } }, [
        createEl("div", { class: "d-flex gap-4" }, [
          createEl("div", { class: "flex-shrink-0" }, [
            createEl("div", {
              class: "cn-step-badge d-flex align-items-center justify-content-center rounded-circle fw-bold",
              style: "width:44px;height:44px;background:var(--cn-primary);color:#fff;",
              textContent: String(i).padStart(2, "0"),
            }),
          ]),
          createEl("div", { class: "flex-grow-1 d-flex gap-2" }, [
            createEl("textarea", {
              class: "form-control cn-input",
              name: "step_instructions[]",
              rows: 2,
              placeholder: "Describe this step...",
            }),
            createEl(
              "button",
              { class: "btn btn-link text-decoration-none text-danger px-2", type: "button", title: "Remove step" },
              [createEl("span", { class: "material-symbols-outlined", textContent: "delete" })]
            ),
          ]),
        ]),
      ]);

      $("button", step).addEventListener("click", () => {
        step.remove();
        renumberSteps();
      });

      stepsWrap.append(step);
    });
  }
}

function initAuthModalsFromQuery() {
  const params = new URLSearchParams(window.location.search);
  if (params.get("login") === "1") {
    const el = document.getElementById("cnModalSignIn");
    if (el && window.bootstrap?.Modal) {
      window.bootstrap.Modal.getOrCreateInstance(el).show();
    }
  }
  if (params.get("register") === "1") {
    const el = document.getElementById("cnModalSignUp");
    if (el && window.bootstrap?.Modal) {
      window.bootstrap.Modal.getOrCreateInstance(el).show();
    }
  }
}

function initNavSearch() {
  const wraps = $all(".cn-nav-search-wrap");
  if (!wraps.length) return;

  const esc = (s) => {
    const d = document.createElement("div");
    d.textContent = s;
    return d.innerHTML;
  };

  function hideAll() {
    wraps.forEach((w) => {
      const box = w.querySelector(".cn-nav-search-results");
      if (box) {
        box.style.display = "none";
        box.hidden = true;
        box.innerHTML = "";
      }
    });
  }

  function show(box, html) {
    box.innerHTML = html;
    box.style.display = html ? "block" : "none";
    box.hidden = !html;
  }

  wraps.forEach((wrap) => {
    const input = wrap.querySelector(".cn-nav-search-input");
    const box = wrap.querySelector(".cn-nav-search-results");
    if (!input || !box) return;

    let timer;
    input.addEventListener("input", () => {
      clearTimeout(timer);
      const q = input.value.trim();
      $all(".cn-nav-search-input").forEach((el) => {
        if (el !== input) el.value = input.value;
      });
      if (q.length < 2) {
        hideAll();
        return;
      }
      timer = setTimeout(async () => {
        try {
          const u = new URL("search-suggest.php", window.location.href);
          u.searchParams.set("q", q);
          const res = await fetch(u.toString(), { credentials: "same-origin" });
          if (!res.ok) throw new Error("x");
          const data = await res.json();
          const rows = data.recipes || [];
          const html = rows.length
            ? rows
                .map(
                  (r) =>
                    `<a role="option" class="list-group-item list-group-item-action py-2 px-3 text-decoration-none text-reset" href="recipe-details.php?slug=${encodeURIComponent(r.slug)}"><div class="fw-semibold small cn-headline" style="color:var(--cn-primary)">${esc(r.title)}</div><div class="small cn-text-muted">${esc(r.category_label || "")}</div></a>`
                )
                .join("")
            : `<div class="list-group-item py-2 px-3 small cn-text-muted">No recipes found</div>`;
          wraps.forEach((w) => {
            const b = w.querySelector(".cn-nav-search-results");
            if (b) show(b, html);
          });
        } catch {
          hideAll();
        }
      }, 280);
    });

    input.addEventListener("keydown", (e) => {
      if (e.key === "Escape") hideAll();
    });
  });

  document.addEventListener(
    "click",
    (e) => {
      if (!e.target.closest(".cn-nav-search-wrap")) hideAll();
    },
    true
  );
}

document.addEventListener("DOMContentLoaded", () => {
  initActiveNav();
  initCreateRecipeForm();
  initAuthModalsFromQuery();
  initNavSearch();
});

