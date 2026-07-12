<?php
$errors = [];
$email = "";

require_once(__DIR__ . "/../../lib/app.php");

if (isset($_POST["email"], $_POST["password"], $_POST["confirm_password"])) {
    // Existing input cleanup and validation stay above the database code.

    if (empty($errors)) {
        try {
            // Existing getDB(), password_hash(), prepare(), and execute() stay here.

            error_log("Registration insert succeeded for user id " . $db->lastInsertId());
            // Replace the temporary success echo with flash + redirect.
            flash("Account created. Please log in.", "success");
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $errors[] = "That email is already registered.";
            } else {
                error_log("Registration failed: " . $e->getMessage());
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }

    // Any validation or PDO errors collected above show on the same form.
    flash_errors($errors);
    // Keep validation failures on this request so sticky form values remain.
    // header("Location: register.php");
    // exit;
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>

<body>
    <?php render_nav(); ?>
    <h1>Register</h1>
    <form method="post" action="register.php" onsubmit="return validate(this);">

        <label for="email">Email</label>
        <input id="email" name="email" type="email"
            required autocomplete="email"
            value="<?php echo htmlspecialchars($email); ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password"
            required minlength="8" autocomplete="new-password">

        <label for="confirm_password">Confirm Password</label>
        <input id="confirm_password" name="confirm_password" type="password"
            required minlength="8" autocomplete="new-password">

        <button type="submit">Register</button>
    </form>

    <script>
        function validate(form) {
            const errors = [];

            validate_email(form.email, errors);
            validate_password(form.password, errors);
            validate_passwords_match(form.password, form.confirm_password, errors);

            return show_validation_errors(errors);
        }
    </script>
    <?php render_flash_messages(); ?>
</body>

</html>
