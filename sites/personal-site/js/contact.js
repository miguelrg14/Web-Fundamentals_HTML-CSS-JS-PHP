// The site is static (no server), so the contact form opens the visitor's email app
// with the message already written instead of posting it anywhere.
const RECIPIENT = "miguelrodriguezgallego1@gmail.com";

document.getElementById("contact-form").addEventListener("submit", (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const name = form.elements.name.value.trim();
    const message = form.elements.message.value.trim();

    const subject = encodeURIComponent(`Message from ${name}`);
    const body = encodeURIComponent(`${message}\n\n${name}`);
    window.location.href = `mailto:${RECIPIENT}?subject=${subject}&body=${body}`;
});
