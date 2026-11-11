const container = document.getElementById("container");

const signUpBtn = document.getElementById("signUpBtn");
const signInBtn = document.getElementById("signInBtn");

signUpBtn.addEventListener("click", function () {
    container.classList.add("right-panel-active");
});

signInBtn.addEventListener("click", function () {
    container.classList.remove("right-panel-active");
});

// Demo form submissions only.
// Connect these forms to a secure backend for real accounts.

document.getElementById("signupForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    try {
        const response = await fetch("register.php", {
            method: "POST",
            body: new FormData(this)
        });

        const result = await response.json();

        if (result.success) {
            alert("Account created successfully! You can now sign in.");
            this.reset();

            document.getElementById("container")
                .classList.remove("right-panel-active");
        } else {
            alert(result.message || "Registration failed.");
        }
    } catch (error) {
        alert("Unable to connect to the server. Please try again.");
        console.error(error);
    }
});

document.getElementById("signinForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch("login.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert("Login successful!");

            // Redirect after successful login.
            window.location.href = "dashboard.php";
        } else {
            alert("Intruders! DONT COME IN!!!");
        }
    } catch (error) {
        alert("Unable to connect to the server. Please try again.");
        console.error(error);
    }
});

document.getElementById("forgotPassword").addEventListener("click", function (e) {
    e.preventDefault();
    alert("Connect a password recovery feature to reset your password.");
});