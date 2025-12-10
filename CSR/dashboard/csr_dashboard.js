/* ============================================================
   CSR DASHBOARD — CLEAN / FINAL JS
============================================================ */

// Sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.querySelector(".sidebar-overlay");

    const isActive = sidebar.classList.toggle("active");
    overlay.classList.toggle("active", isActive);
}

// Navigation with loader
function navigate(tab) {
    showLoader();
    window.location = "csr_dashboard.php?tab=" + tab;
}

// Loader show / hide
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
   AUTO-CLOSE SIDEBAR ON OUTSIDE CLICK
============================================================ */
document.addEventListener("click", function (e) {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.querySelector(".sidebar-overlay");

    if (!sidebar.classList.contains("active")) return;

    if (!sidebar.contains(e.target) &&
        !e.target.classList.contains("hamburger") &&
        !e.target.closest(".hamburger")) {

        sidebar.classList.remove("active");
        overlay.classList.remove("active");
    }
});

/* ============================================================
   SMART RESIZE BEHAVIOR
============================================================ */
let lastWidth = window.innerWidth; // <-- Only declared ONCE.

window.addEventListener("resize", () => {
    const now = window.innerWidth;

    if (lastWidth <= 900 && now > 900) {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.querySelector(".sidebar-overlay");
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
    }

    lastWidth = now;
});
