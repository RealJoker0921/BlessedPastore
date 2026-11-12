<?php
session_start();
require 'dbcon.php';

if (isset($_SESSION["user_id"], $_SESSION["user_name"])) {
    header("Location: dashboard.php");
    exit;
}

$message = "";
$activePanel = "";
$signupName = "";
$signupEmail = "";
$signinEmail = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "signup") {
        $signupName = trim($_POST["name"] ?? "");
        $signupEmail = strtolower(trim($_POST["signupEmail"] ?? ""));
        $signupPassword = $_POST["signupPassword"] ?? "";
        $activePanel = "right-panel-active";

        if ($signupName === "" || !filter_var($signupEmail, FILTER_VALIDATE_EMAIL)) {
            $message = "Enter your name and a valid email address.";
        } elseif (strlen($signupPassword) < 5) {
            $message = "Password must be at least 5 characters.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$signupEmail]);

                if ($stmt->fetch()) {
                    $message = "An account with that email already exists.";
                } else {
                   $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");

                    $stmt->execute([
                        $signupName,
                        $signupEmail,
                        password_hash($signupPassword, PASSWORD_DEFAULT)
                    ]);

                    session_regenerate_id(true);
                    $_SESSION["user_id"] = (int)$pdo->lastInsertId();
                    $_SESSION["user_name"] = $signupName;
                    header("Location: dashboard.php");
                    exit;
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $message = "Unable to create your account right now. Please try again.";
            }
        }
    } elseif ($action === "signin") {
        $signinEmail = strtolower(trim($_POST["signinEmail"] ?? ""));
        $signinPassword = $_POST["signinPassword"] ?? "";

        try {
            $stmt = $pdo->prepare(
                "SELECT id, name, password FROM users WHERE email = ? LIMIT 1"
            );
            $stmt->execute([$signinEmail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($signinPassword, $user["password"])) {
                session_regenerate_id(true);
                $_SESSION["user_id"] = (int)$user["id"];
                $_SESSION["user_name"] = $user["name"];
                header("Location: dashboard.php");
                exit;
            }

            $message = "Email or password is incorrect.";
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = "Unable to sign in right now. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login and Signup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<div class="container <?= htmlspecialchars($activePanel, ENT_QUOTES, "UTF-8") ?>" id="container">

    <!-- SIGN UP FORM -->
    <div class="form-container sign-up-container">
        <form id="signupForm" method="post">
            <h1>Create Account</h1>
            <?php if ($activePanel !== "" && $message !== ""): ?>
                <p class="form-message" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>
            <br></br>
            <input type="hidden" name="action" value="signup">
            <input type="text" placeholder="Name" name="name" value="<?= htmlspecialchars($signupName, ENT_QUOTES, "UTF-8") ?>" required>
            <input type="email" placeholder="Email" name="signupEmail" value="<?= htmlspecialchars($signupEmail, ENT_QUOTES, "UTF-8") ?>" required>
            <input type="password" placeholder="Password"
                   name="signupPassword" minlength="5" required>

            <button type="submit">SIGN UP</button>
        </form>
    </div>

    <!-- SIGN IN FORM -->
    <div class="form-container sign-in-container">
        <form id="signinForm" method="post">
            <h1>Sign in</h1>
            <?php if ($activePanel === "" && $message !== ""): ?>
                <p class="form-message" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>
            <br></br>

            <input type="hidden" name="action" value="signin">
            <input type="email" placeholder="Email" name="signinEmail" value="<?= htmlspecialchars($signinEmail, ENT_QUOTES, "UTF-8") ?>" required>
            <input type="password" placeholder="Password"
                   name="signinPassword" minlength="5" required>

            <a href="#" id="forgotPassword">Forgot your password?</a>

            <button type="submit">SIGN IN</button>
        </form>
    </div>

    <!-- BLUE OVERLAY -->
    <div class="overlay-container">
        <div class="overlay">

            <!-- RIGHT PANEL: DEFAULT -->
            <div class="overlay-panel overlay-right">
                <h2>Hello, Friend!</h2>
                <p>Enter your personal details and start your journey with us</p>
                <button class="ghost" id="signUpBtn" type="button">
                    SIGN UP
                </button>
            </div>

            <!-- LEFT PANEL: AFTER SWITCHING -->
            <div class="overlay-panel overlay-left">
                <h2>Welcome Back!</h2>
                <p>To keep connected with us please login with your personal info</p>
                <button class="ghost" id="signInBtn" type="button">
                    SIGN IN
                </button>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="script.js"></script>
</body>
</html>