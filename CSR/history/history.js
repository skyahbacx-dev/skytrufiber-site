/* ============================================================
   HISTORY JS — Scroll, Jump Buttons, Auto Behavior
   ============================================================ */

console.log("History JS Loaded");

// Wait until page fully loads
document.addEventListener("DOMContentLoaded", () => {

    /* ------------------------------------------------------------
       FIND CHAT CONTAINER (only exists on history_view.php)
    ------------------------------------------------------------ */
    const chatBox = document.getElementById("chatHistory");
    const jumpTop = document.getElementById("jumpTop");
    const jumpBottom = document.getElementById("jumpBottom");

    /* ------------------------------------------------------------
       AUTO-SCROLL CHAT TO BOTTOM (if chat exists)
    ------------------------------------------------------------ */
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    /* ------------------------------------------------------------
       JUMP TO TOP / BOTTOM WITH SMOOTH SCROLL
    ------------------------------------------------------------ */

    if (jumpTop) {
        jumpTop.addEventListener("click", () => {
            if (chatBox) {
                chatBox.scrollTo({ top: 0, behavior: "smooth" });
            } else {
                window.scrollTo({ top: 0, behavior: "smooth" });
            }
        });
    }

    if (jumpBottom) {
        jumpBottom.addEventListener("click", () => {
            if (chatBox) {
                chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: "smooth" });
            } else {
                window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
            }
        });
    }


    /* ------------------------------------------------------------
       FILTER BUTTON ACTIVE STATE (visual highlight)
    ------------------------------------------------------------ */
    const filterButtons = document.querySelectorAll(".filter-btn");

    if (filterButtons.length > 0) {
        const urlParams = new URLSearchParams(window.location.search);
        const activeFilter = urlParams.get("filter") || "all";

        filterButtons.forEach(btn => {
            if (btn.href.includes("filter=" + activeFilter)) {
                btn.classList.add("active");
            } else {
                btn.classList.remove("active");
            }
        });
    }


    /* ------------------------------------------------------------
       AUTO-HIGHLIGHT TIMELINE ACTION COLORS (already handled by CSS)
       This section ensures invalid or unknown actions do not break UI.
    ------------------------------------------------------------ */
    document.querySelectorAll(".log-entry").forEach(entry => {
        // If no color class was added in PHP, add a default
        if (![...entry.classList].some(c =>
            ["pending", "assigned", "unassigned", "resolved", "unresolved"].includes(c)
        )) {
            entry.classList.add("default");
        }
    });

});
