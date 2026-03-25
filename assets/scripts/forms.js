const form = document.querySelector("form");
const countrySelect = document.querySelector('select[name="country_code"]');

const nameInput = document.querySelector('input[name="name"]');
const emailInput = document.querySelector('input[name="email"]');
const usernameInput = document.querySelector('input[name="username"]');
const passwordInput = document.querySelector('input[name="password"]');
const termsCheckbox = document.querySelector('input[name="accept_terms"]');

/* ---------------------------
   Load Country Codes
---------------------------- */
fetch("../assets/scripts/country-codes.json")
    .then(response => response.json())
    .then(data => {
        const countries = Object.values(data);

        countries.forEach(country => {
            const option = document.createElement("option");
            option.value = country.phone[0];
            option.textContent = `${country.emoji} ${country.name} (${country.phone[0]})`;
            countrySelect.appendChild(option);
        });
    })
    .catch(error => console.error("Error loading JSON:", error));


/* ---------------------------
   Validation
---------------------------- */
form.addEventListener("submit", function (e) {

    let errors = [];

    // Remove old error messages
    document.querySelectorAll(".error-message").forEach(el => el.remove());

    // Name validation
    if (nameInput.value.trim() === "") {
        errors.push({ field: nameInput, message: "Name is required." });
    }

    // Email validation
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (emailInput.value.trim() === "") {
        errors.push({ field: emailInput, message: "Email is required." });
    } else if (!emailPattern.test(emailInput.value)) {
        errors.push({ field: emailInput, message: "Enter a valid email address." });
    }

    // Username validation
    if (usernameInput.value.trim() === "") {
        errors.push({ field: usernameInput, message: "Username is required." });
    }

    // Password validation
    if (passwordInput.value.length < 8) {
        errors.push({ field: passwordInput, message: "Password must be at least 8 characters." });
    }

    // Terms validation
    if (!termsCheckbox.checked) {
        errors.push({ field: termsCheckbox, message: "You must accept the terms." });
    }

    // If errors exist
    if (errors.length > 0) {
        e.preventDefault();

        errors.forEach(error => {
            showError(error.field, error.message);
        });
    }
});


function showError(input, message) {

    const error = document.createElement("div");
    error.className = "error-message";
    error.style.color = "red";
    error.style.fontSize = "0.85rem";
    error.style.marginTop = "5px";
    error.textContent = message;

    input.closest(".input-box").appendChild(error);
}