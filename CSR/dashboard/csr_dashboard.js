// Sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.querySelector(".sidebar-overlay");

    sidebar.classList.toggle("active");
    overlay.classList.toggle("active");
}

// Navigation with loader
function navigate(tab) {
    showLoader();
    window.location = "csr_dashboard.php?tab=" + tab;
}

// Loader show/hide
function showLoader() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) overlay.style.display = "flex";
}

function hideLoader() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) overlay.style.display = "none";
}

// Hide loader when the dashboard is ready
document.addEventListener("DOMContentLoaded", hideLoader);
