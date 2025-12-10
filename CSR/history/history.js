console.log("History JS Loaded");

// Scrollable chat container
const chatBox = document.getElementById("chatHistory");

// Jump to top
document.getElementById("jumpTop")?.addEventListener("click", () => {
    chatBox.scrollTo({ top: 0, behavior: "smooth" });
});

// Jump to bottom
document.getElementById("jumpBottom")?.addEventListener("click", () => {
    chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: "smooth" });
});
