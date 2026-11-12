const container = document.getElementById("container");

const signUpBtn = document.getElementById("signUpBtn");
const signInBtn = document.getElementById("signInBtn");

signUpBtn.addEventListener("click", function () {
    container.classList.add("right-panel-active");
});

signInBtn.addEventListener("click", function () {
    container.classList.remove("right-panel-active");
});

document.querySelectorAll(".toggle-password").forEach((button) => {
    button.addEventListener("click", function () {
        const targetId = this.dataset.target;
        const passwordInput = document.getElementById(targetId);

        if (!passwordInput) return;

        const shouldShow = passwordInput.type === "password";
        passwordInput.type = shouldShow ? "text" : "password";
        this.textContent = shouldShow ? "Hide" : "Show";
        this.setAttribute("aria-label", shouldShow ? "Hide password" : "Show password");
    });
});

document.getElementById("forgotPassword").addEventListener("click", function (e) {
    e.preventDefault();
    alert("Connect a password recovery feature to reset your password.");
});