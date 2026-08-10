<?php
require_once(__DIR__ . "/../../lib/app.php");
$errors = [];
$email = "";
$username = "";

if (isset($_POST["email"], $_POST["username"], $_POST["password"], $_POST["confirm_password"])) {
    require_csrf_token(project_url("register.php"));
    $email = sanitize_email($_POST["email"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    validate_email($email, $errors);
    validate_username($username, $errors);
    validate_password($password, $errors);
    validate_passwords_match($password, $confirmPassword, $errors);

    if (empty($errors)) {
        // TODO: connect to the database, hash the password, and insert the user.
        try {
            $db = getDB();
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare(
                "INSERT INTO Users (email, username, password_hash)
             VALUES (:email, :username, :password_hash)"
            );
            $stmt->execute([
                ":email" => $email,
                ":username" => $username,
                ":password_hash" => $hash,
            ]);

            error_log("Registration insert succeeded for user id " . $db->lastInsertId());
            flash("Account created. Please log in.", "success");
            $email = "";
            $username = "";
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                duplicate_user_detail($e, $errors);
            } else {
                error_log("Registration failed: " . $e->getMessage());
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
    // Any validation or PDO errors collected above redirect back to the form.
    flash_errors($errors);
    //header("Location: register.php");
    //exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Register"); ?>
</head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Register</h1>

        <form method="post" action="register.php" onsubmit="return validate(this);">

            <?php
            render_csrf_input();
            render_input([
                "type" => "email",
                "name" => "email",
                "label" => "Email",
                "value" => $email,
                "attributes" => ["required" => true, "autocomplete" => "email"],
            ]);

            render_input([
                "name" => "username",
                "label" => "Username",
                "value" => $username,
                "attributes" => [
                    "required" => true,
                    "minlength" => 3,
                    "maxlength" => 30,
                    "pattern" => "[a-z0-9_\\-]{3,30}",
                    "autocomplete" => "username",
                ],
            ]);

            render_input([
                "type" => "password",
                "name" => "password",
                "label" => "Password",
                "attributes" => ["required" => true, "minlength" => 8, "autocomplete" => "new-password"],
            ]);

            render_input([
                "type" => "password",
                "name" => "confirm_password",
                "label" => "Confirm Password",
                "attributes" => ["required" => true, "minlength" => 8, "autocomplete" => "new-password"],
            ]);
            render_button(["text" => "Register"]);
            ?>
        </form>
    </main>
    <script>
        function validate(form) {

            const errors = [];

            validate_email(form.email, errors);
            validate_username(form.username, errors);
            validate_password(form.password, errors);
            validate_passwords_match(form.password, form.confirm_password, errors);

            return show_validation_errors(errors);
        }
    </script>
    <!-- Last PHP inside <body> so it captures messages queued during this request. -->
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>