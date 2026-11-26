<?php
session_start();
require 'dbcon.php';

if (isset($_SESSION["user_id"], $_SESSION["user_name"])) {
    header("Location: dashboard.php");
    exit;
}

$message = "";
$signinEmail = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "signin") {
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
    <title>Owner Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<div class="container" id="container">
    <!-- SIGN IN FORM -->
    <div class="form-container sign-in-container">
        <form id="signinForm" method="post">
            <h1>Owner Sign In</h1>
            <?php if ($message !== ""): ?>
                <p class="form-message" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <input type="hidden" name="action" value="signin">
            <input type="email" placeholder="Owner Email" name="signinEmail" value="<?= htmlspecialchars($signinEmail, ENT_QUOTES, "UTF-8") ?>" required>
            <input type="password" placeholder="Password" name="signinPassword" required>
            <button type="submit">SIGN IN</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="script.js"></script>
</body>
</html>