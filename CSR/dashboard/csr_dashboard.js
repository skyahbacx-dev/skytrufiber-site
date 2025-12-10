/* ============================================================
   CSR DASHBOARD — MAIN JS CONTROLLER
   Handles:
   - Sidebar toggle (supports icon-only mode)
   - Loader overlay
   - Navigation switching
   ============================================================ */

// Toggle Sidebar
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

// Show loader overlay
function showLoader() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) overlay.style.display = "flex";
}

// Hide loader overlay
function hideLoader() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) overlay.style.display = "none";
}

// Auto-hide loader when dashboard is fully loaded
document.addEventListener("DOMContentLoaded", hideLoader);


/* ============================================================
   AUTO-CLOSE SIDEBAR ON OUTSIDE CLICK
   (Improves UX on mobile and narrow screens)
   ============================================================ */

document.addEventListener("click", function (e) {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.querySelector(".sidebar-overlay");

    // Ignore click if sidebar is not active
    if (!sidebar.classList.contains("active")) return;

    // If clicking outside sidebar → close it
    if (!sidebar.contains(e.target) && !e.target.classList.contains("hamburger")) {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
    }
});


/* ============================================================
   AUTO-COLLAPSE SIDEBAR ON RESIZE (optional, smart behavior)
   ============================================================ */

let screenWidth = window.innerWidth;

window.addEventListener("resize", () => {
    const newWidth = window.innerWidth;

    // If dashboard was open and window expands → auto-close mobile sidebar
    if (newWidth > 900 && screenWidth <= 900) {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.querySelector(".sidebar-overlay");
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
    }

    screenWidth = newWidth;
});
