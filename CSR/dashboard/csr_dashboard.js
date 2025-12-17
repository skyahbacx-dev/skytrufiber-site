/* ============================================================
   CSR DASHBOARD — CLEAN / FINAL JS (FIXED)
============================================================ */

/* =========================
   SIDEBAR TOGGLE
========================= */
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.querySelector(".sidebar-overlay");
    const dashboard = document.querySelector(".dashboard-container");

    if (!sidebar || !overlay || !dashboard) return;

    const isActive = sidebar.classList.toggle("active");
    overlay.classList.toggle("active", isActive);
    dashboard.classList.toggle("shifted", isActive);
}

/* =========================
   NAVIGATION WITH LOADER
========================= */
function navigate(tab) {
    showLoader();
    window.location.href = "csr_dashboard.php?tab=" + tab;
}

/* =========================
   LOADER
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
    const sidebar = document.getElementById("sidebar");
    const overlay = document.querySelector(".sidebar-overlay");
    const hamburger = document.querySelector(".hamburger");

    if (!sidebar || !overlay) return;
    if (!sidebar.classList.contains("active")) return;

    if (
        !sidebar.contains(e.target) &&
        !hamburger.contains(e.target)
    ) {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        document.querySelector(".dashboard-container")
            ?.classList.remove("shifted");
    }
});

/* =========================
   RESPONSIVE BEHAVIOR
========================= */
let lastWidth = window.innerWidth;

window.addEventListener("resize", () => {
    const now = window.innerWidth;

    if (lastWidth <= 900 && now > 900) {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.querySelector(".sidebar-overlay");
        const dashboard = document.querySelector(".dashboard-container");

        sidebar?.classList.remove("active");
        overlay?.classList.remove("active");
        dashboard?.classList.remove("shifted");
    }

    lastWidth = now;
});
