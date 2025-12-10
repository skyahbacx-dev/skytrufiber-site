/* ============================================================
   CSR DASHBOARD — MAIN JS CONTROLLER (UPDATED)
   Supports:
   - Icon-only collapsed sidebar
   - Expandable full sidebar
   - Loader overlay
   - Tab navigation
   - Auto-close sidebar on outside click
   - Smart mobile behavior
   ============================================================ */

/* ============================================================
   SIDEBAR TOGGLE (EXPANDS / COLLAPSES FULL SIDEBAR)
   ============================================================ */
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.querySelector(".sidebar-overlay");

    const willOpen = !sidebar.classList.contains("active");

    sidebar.classList.toggle("active", willOpen);
    overlay.classList.toggle("active", willOpen);
}

/* ============================================================
   NAVIGATION WITH LOADER
   ============================================================ */
function navigate(tab) {
    showLoader();
    window.location = "csr_dashboard.php?tab=" + tab;
}

/* ============================================================
   LOADING OVERLAY
   ============================================================ */
function showLoader() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) overlay.style.display = "flex";
}

function hideLoader() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) overlay.style.display = "none";
}

document.addEventListener("DOMContentLoaded", hideLoader);

/* ============================================================
   CLICK OUTSIDE → CLOSE SIDEBAR
   ============================================================ */
document.addEventListener("click", (e) => {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.querySelector(".sidebar-overlay");

    // Sidebar not open → ignore
    if (!sidebar.classList.contains("active")) return;

    // Clicking hamburger → ignore
    if (e.target.classList.contains("hamburger")) return;

    // Clicking inside sidebar → ignore
    if (sidebar.contains(e.target)) return;

    // Otherwise → close sidebar
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
});

/* ============================================================
   ICON BAR → OPEN SIDEBAR
   (When user clicks an icon on the collapsed sidebar)
   ============================================================ */
document.addEventListener("click", (e) => {
    if (e.target.closest(".sidebar-collapsed .icon-btn")) {
        // Expand the real sidebar
        document.getElementById("sidebar").classList.add("active");
        document.querySelector(".sidebar-overlay").classList.add("active");
    }
});

/* ============================================================
   RESPONSIVE AUTO-CLOSE (Mobile behavior)
   ============================================================ */
let lastWidth = window.innerWidth;

window.addEventListener("resize", () => {
    const width = window.innerWidth;
    const sidebar = document.getElementById("sidebar");
    const overlay = document.querySelector(".sidebar-overlay");

    // When screen grows from mobile to desktop → hide overlay sidebar
    if (width > 900 && lastWidth <= 900) {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
    }

    lastWidth = width;
});
