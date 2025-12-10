document.querySelectorAll("#historyTable th[data-sort]").forEach(header => {

    header.addEventListener("click", () => {

        const table = header.closest("table");
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));

        const type = header.getAttribute("data-sort");
        const index = Array.from(header.parentNode.children).indexOf(header);

        const asc = !header.classList.contains("asc");
        table.querySelectorAll("th").forEach(h => h.classList.remove("asc", "desc"));
        header.classList.add(asc ? "asc" : "desc");

        rows.sort((a, b) => {

            let x = a.children[index].innerText.trim();
            let y = b.children[index].innerText.trim();

            if (type === "number") {
                x = parseInt(x.replace("#", ""));
                y = parseInt(y.replace("#", ""));
            }

            if (type === "date") {
                x = new Date(x);
                y = new Date(y);
            }

            if (asc) return x > y ? 1 : -1;
            else return x < y ? 1 : -1;
        });

        rows.forEach(r => tbody.appendChild(r));
    });

});
