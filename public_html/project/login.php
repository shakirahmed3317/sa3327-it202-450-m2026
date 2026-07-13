<?php
require_once(__DIR__ . "/../../lib/app.php");

$errors = [];
$email = "";
$user = false;

if (isset($_POST["email"], $_POST["password"])) {
    $email = sanitize_email($_POST["email"]);
    $password = $_POST["password"];

    validate_email($email, $errors);
    validate_password($password, $errors);

    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                "SELECT id AS user_id, email, username, password_hash
                 FROM Users
                 WHERE email = :email
                 LIMIT 1"
            );
            $stmt->execute([":email" => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Login query failed: " . $e->getMessage());
            $errors[] = "Login failed. Please try again.";
        }
    }

    if (
        empty($errors)
        && (!$user || !password_verify($password, $user["password_hash"]))
    ) {
        $errors[] = "Invalid email or password.";
    }

    if (empty($errors)) {
        session_regenerate_id(true);
        $user["user_id"] = (int) $user["user_id"];
        unset($user["password_hash"]);
        $user["roles"] = get_user_roles($user["user_id"]);
        $_SESSION["user"] = $user;
        flash("Welcome back.", "success");
        header("Location: dashboard.php");
        exit;
    }
    // Any validation, lookup, or password errors collected above show on the same form.
    flash_errors($errors);
    // Keep validation failures on this request so sticky form values remain.
    // header("Location: login.php");
    // exit;
}

$message = implode("<br>", array_map("htmlspecialchars", $errors));
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>

<body>
    <?php render_nav(); ?>
    <h1>Login</h1>
    <form method="post" action="login.php" onsubmit="return validate(this)">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required
            autocomplete="email"
            value="<?php echo htmlspecialchars($email); ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password"
            required minlength="8"
            autocomplete="current-password">

        <button type="submit">Login</button>
    </form>
    <script>
        function validate(form) {
            const errors = [];

            validate_email(form.email, errors);
            validate_password(form.password, errors);

            return show_validation_errors(errors);
        }
    </script>
        <!-- Last PHP inside <body> so it captures messages queued during this request. -->
    <?php render_flash_messages(); ?>
</body>

</html>