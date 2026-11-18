// Connects the form to the DNI logic in dni.js.
const form = document.getElementById("dni-form");
const input = document.getElementById("dni-input");
const output = document.getElementById("dni-result");
const message = document.getElementById("dni-message");
const helpButton = document.getElementById("help-button");
const help = document.getElementById("help");

function showResult(result) {
    form.classList.toggle("valid", result.ok);
    form.classList.toggle("invalid", !result.ok);
    input.setAttribute("aria-invalid", String(!result.ok));
    output.value = result.ok ? result.letter : "—";
    message.textContent = result.ok ? `Full ID: ${result.id}` : result.error;
}

form.addEventListener("submit", (event) => {
    event.preventDefault();
    showResult(Dni.check(input.value));
});

// Typing again clears the previous result.
input.addEventListener("input", () => {
    form.classList.remove("valid", "invalid");
    input.removeAttribute("aria-invalid");
    output.value = "";
    message.textContent = "";
});

helpButton.addEventListener("click", () => {
    const opening = help.hidden;
    help.hidden = !opening;
    helpButton.setAttribute("aria-expanded", String(opening));
});
