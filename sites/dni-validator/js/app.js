// Connects the page to the DNI logic in dni.js.
const form = document.getElementById("dni-form");
const input = document.getElementById("dni-input");
const output = document.getElementById("dni-result");
const message = document.getElementById("dni-message");
const fullId = document.querySelector(".full-id");
const fullIdText = document.getElementById("dni-full");
const copyButton = document.getElementById("copy-button");
const sum = document.getElementById("dni-sum");
const table = document.getElementById("letter-table");

// The table of the 23 letters, numbered from 0, so the matching one can be highlighted.
const cells = [...Dni.LETTERS].map((letter, remainder) => {
    const cell = document.createElement("li");
    cell.innerHTML = `<span>${remainder}</span><strong>${letter}</strong>`;
    table.append(cell);
    return cell;
});

function highlight(remainder) {
    cells.forEach((cell, i) => cell.classList.toggle("current", i === remainder));
}

function reset() {
    form.classList.remove("valid", "invalid");
    input.removeAttribute("aria-invalid");
    output.value = "?";
    message.textContent = "Type a number to start.";
    fullId.hidden = true;
    highlight(-1);
}

function show(result) {
    form.classList.toggle("valid", result.ok);
    form.classList.toggle("invalid", !result.ok);
    input.setAttribute("aria-invalid", String(!result.ok));

    if (!result.ok) {
        output.value = "!";
        message.textContent = result.error;
        fullId.hidden = true;
        highlight(-1);
        return;
    }

    output.value = result.letter;
    message.textContent = "Complete ID:";
    fullIdText.textContent = result.id;
    copyButton.textContent = "Copy";
    fullId.hidden = false;
    sum.textContent = `${result.number} mod 23 = ${result.remainder} → ${result.letter}`;
    highlight(result.remainder);
}

form.addEventListener("submit", (event) => {
    event.preventDefault();
    show(Dni.check(input.value));
});

input.addEventListener("input", reset);

copyButton.addEventListener("click", async () => {
    try {
        await navigator.clipboard.writeText(fullIdText.textContent);
        copyButton.textContent = "Copied!";
    } catch {
        copyButton.textContent = "Can't copy";
    }
});

highlight(14); // matches the example under the table
