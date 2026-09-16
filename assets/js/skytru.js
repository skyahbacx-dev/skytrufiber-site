/* ==========================================================================
   SKYTRU shared JS toolkit.
   Small, dependency-free (jQuery already loads separately for the older
   AJAX endpoints - this file only handles the new shared UI chrome so it
   doesn't need jQuery at all).
   ========================================================================== */

const Sky = {
  /* ---- sidebar (mobile) ---- */
  toggleSidebar() {
    document.querySelector(".sky-sidebar")?.classList.toggle("open");
  },
  closeSidebar() {
    document.querySelector(".sky-sidebar")?.classList.remove("open");
  },

  /* ---- toast ---- */
  toast(message, type = "info", ms = 3200) {
    let stack = document.querySelector(".sky-toast-stack");
    if (!stack) {
      stack = document.createElement("div");
      stack.className = "sky-toast-stack";
      document.body.appendChild(stack);
    }
    const el = document.createElement("div");
    el.className = `sky-toast ${type}`;
    el.textContent = message;
    stack.appendChild(el);
    setTimeout(() => el.remove(), ms);
  },

  /* ---- simple tabs: <div class="sky-tabs"><button data-tab="x">..</button></div>
         + siblings with [data-tab-panel="x"] ---- */
  initTabs(root = document) {
    root.querySelectorAll(".sky-tabs").forEach((tabs) => {
      tabs.addEventListener("click", (e) => {
        const btn = e.target.closest(".sky-tab");
        if (!btn) return;
        const group = tabs.closest("[data-tab-group]") || tabs.parentElement;
        tabs.querySelectorAll(".sky-tab").forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        const target = btn.dataset.tab;
        group.querySelectorAll("[data-tab-panel]").forEach((panel) => {
          panel.style.display = panel.dataset.tabPanel === target ? "" : "none";
        });
      });
    });
  },

  /* ---- clean, readable navigation (replaces the old base64 token scheme
         for links that don't need it). Falls back gracefully - the old
         home.php?v= links still work untouched. ---- */
  goTab(tab, extraParams = {}) {
    const params = new URLSearchParams({ tab, ...extraParams });
    window.location.href = "/home.php?" + params.toString();
  },
};

document.addEventListener("DOMContentLoaded", () => Sky.initTabs());
