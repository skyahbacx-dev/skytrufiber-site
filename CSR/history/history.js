/* ================================================
   HISTORY SYSTEM JS — isolated from dashboard/chat
================================================ */

document.addEventListener("DOMContentLoaded", () => {

    // Jump to Top
    const topBtn = document.getElementById("jumpTop");
    if (topBtn) {
        topBtn.onclick = () => window.scrollTo({ top: 0, behavior: "smooth" });
    }

    // Jump to Bottom
    const bottomBtn = document.getElementById("jumpBottom");
    if (bottomBtn) {
        bottomBtn.onclick = () =>
            window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
    }

    console.log("History JS Loaded");
});
