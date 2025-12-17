/* ============================================================
   CSR DASHBOARD — CLEAN / FINAL JS
============================================================ */

/* =========================
   SIDEBAR TOGGLE
========================= */
function toggleSidebar() {
    const sidebar   = document.getElementById("sidebar");
    const overlay   = document.querySelector(".sidebar-overlay");
    const dashboard = document.querySelector(".dashboard-container");

    if (!sidebar || !overlay || !dashboard) return;

    const isActive = !sidebar.classList.contains("active");

    sidebar.classList.toggle("active", isActive);
    overlay.classList.toggle("active", isActive);
    dashboard.classList.toggle("shifted", isActive);
}

/* =========================
   LOADER (NAVIGATION SAFE)
========================= */
function showLoader() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) overlay.style.display = "flex";
}

function hideLoader() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) overlay.style.display = "none";
}

document.addEventListener("DOMContentLoaded", hideLoader);

/* =========================
   AUTO-CLOSE SIDEBAR
========================= */
document.addEventListener("click", function (e) {
    const sidebar   = document.getElementById("sidebar");
    const overlay   = document.querySelector(".sidebar-overlay");
    const hamburger = document.querySelector(".hamburger");
    const dashboard = document.querySelector(".dashboard-container");

    if (!sidebar || !overlay || !hamburger) return;
    if (!sidebar.classList.contains("active")) return;

    const clickedInsideSidebar  = sidebar.contains(e.target);
    const clickedHamburger      = hamburger.contains(e.target);
    const clickedIconBar        = e.target.closest(".sidebar-collapsed");

    if (!clickedInsideSidebar && !clickedHamburger && !clickedIconBar) {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        dashboard?.classList.remove("shifted");
    }
});

/* =========================
   OVERLAY CLICK CLOSE
========================= */
document.querySelector(".sidebar-overlay")?.addEventListener("click", () => {
    const sidebar   = document.getElementById("sidebar");
    const overlay   = document.querySelector(".sidebar-overlay");
    const dashboard = document.querySelector(".dashboard-container");

    sidebar?.classList.remove("active");
    overlay?.classList.remove("active");
    dashboard?.classList.remove("shifted");
});

/* =========================
   RESPONSIVE BEHAVIOR
========================= */
let lastWidth = window.innerWidth;

window.addEventListener("resize", () => {
    const now = window.innerWidth;

    // Close sidebar when switching to desktop width
    if (lastWidth <= 900 && now > 900) {
        const sidebar   = document.getElementById("sidebar");
        const overlay   = document.querySelector(".sidebar-overlay");
        const dashboard = document.querySelector(".dashboard-container");

        sidebar?.classList.remove("active");
        overlay?.classList.remove("active");
        dashboard?.classList.remove("shifted");
    }

    lastWidth = now;
});
