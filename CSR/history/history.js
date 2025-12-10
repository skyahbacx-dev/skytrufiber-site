console.log("History JS Loaded");

// Jump Buttons
document.addEventListener("DOMContentLoaded", () => {

    const jumpTop = document.getElementById("jumpTop");
    const jumpBottom = document.getElementById("jumpBottom");

    if (jumpTop) {
        jumpTop.onclick = () => window.scrollTo({ top: 0, behavior: "smooth" });
    }

    if (jumpBottom) {
        jumpBottom.onclick = () =>
            window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
    }
});
