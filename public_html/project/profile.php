<?php
require_once(__DIR__ . "/../../lib/app.php");

if (!is_logged_in()) {
    flash("Please log in first.", "warning");
    header("Location: login.php");
    exit;
}

$currentUserId = get_user_id();
$db = getDB();
$stmt = $db->prepare(
    "SELECT id AS user_id, email, username, password_hash
     FROM Users
     WHERE id = :user_id
     LIMIT 1"
);
$stmt->execute([":user_id" => $currentUserId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    unset($_SESSION["user"]);
    flash("Please log in again.", "warning");
    header("Location: login.php");
    exit;
}

$errors = [];

// TODO 1: Add account-detail handling here.

if (
    isset($_POST["action"], $_POST["username"], $_POST["email"])
    && $_POST["action"] === "details"
) {
    $username = trim($_POST["username"]);
    $email = sanitize_email($_POST["email"]);

    validate_username($username, $errors);
    validate_email($email, $errors);

    if (empty($errors)) {
        try {
            $stmt = $db->prepare(
                "UPDATE Users
                 SET username = :username, email = :email
                 WHERE id = :user_id
                 LIMIT 1"
            );
            $stmt->execute([
                ":username" => $username,
                ":email" => $email,
                ":user_id" => $currentUserId,
            ]);

            $_SESSION["user"]["username"] = $username;
            $_SESSION["user"]["email"] = $email;
            flash("Profile updated.", "success");
            header("Location: profile.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                duplicate_user_detail($e, $errors);
            } else {
                error_log("Profile update failed: " . $e->getMessage());
                $errors[] = "Profile update failed. Please try again.";
            }
        }
    }

    if (!empty($errors)) {
        flash_errors($errors);
        header("Location: profile.php");
        exit;
    }
}


// TODO 2: Add password-change handling here.
if (
    isset($_POST["action"], $_POST["current_password"], $_POST["new_password"], $_POST["confirm_password"])
    && $_POST["action"] === "password"
) {
    $currentPassword = $_POST["current_password"];
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];

    $currentPasswordIsValid = validate_password($currentPassword, $errors);

    if (
        $currentPasswordIsValid
        && !password_verify($currentPassword, $currentUser["password_hash"])
    ) {
        $errors[] = "Current password is incorrect.";
    }

    validate_password($newPassword, $errors);
    validate_passwords_match($newPassword, $confirmPassword, $errors);

    if (empty($errors)) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            "UPDATE Users SET password_hash = :hash WHERE id = :user_id LIMIT 1"
        );
        $stmt->execute([":hash" => $hash, ":user_id" => $currentUserId]);
        flash("Password updated.", "success");
        header("Location: profile.php");
        exit;
    }

    if (!empty($errors)) {
        flash_errors($errors);
        header("Location: profile.php");
        exit;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Profile"); ?>
</head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Profile</h1>

        <!-- TODO 3: Add the account-detail and password-change forms here. -->
        <form method="post" action="profile.php" onsubmit="return validate(this);">
            <?php
            render_input(["type" => "hidden", "name" => "action", "value" => "details"]);
            render_input([
                "name" => "username",
                "value" => $currentUser["username"],
                "attributes" => [
                    "required" => true,
                    "minlength" => 3,
                    "maxlength" => 30,
                    "pattern" => "[a-z0-9_\\-]{3,30}",
                    "title" => "Must be lowercase alphanumeric and can use underscore or hyphens",
                    "autocomplete" => "username",
                ],
            ]);
            render_input([
                "type" => "email",
                "name" => "email",
                "value" => $currentUser["email"],
                "attributes" => ["required" => true, "autocomplete" => "email"],
            ]);
            render_button(["text" => "Update Profile"]);
            ?>
        </form>

        <form method="post" action="profile.php" onsubmit="return validate(this);">
            <?php
            render_input(["type" => "hidden", "name" => "action", "value" => "password"]);
            render_input([
                "type" => "password",
                "name" => "current_password",
                "label" => "Current Password",
                "attributes" => [
                    "required" => true,
                    "minlength" => 8,
                    "autocomplete" => "current-password",
                ],
            ]);
            render_input([
                "type" => "password",
                "name" => "new_password",
                "label" => "New Password",
                "attributes" => [
                    "required" => true,
                    "minlength" => 8,
                    "autocomplete" => "new-password",
                ],
            ]);
            render_input([
                "type" => "password",
                "name" => "confirm_password",
                "label" => "Confirm New Password",
                "attributes" => [
                    "required" => true,
                    "minlength" => 8,
                    "autocomplete" => "new-password",
                ],
            ]);
            render_button(["text" => "Change Password", "variant" => "warning"]);
            ?>
        </form>
    </main>
    <script>
        function validate(form) {
            const errors = [];
            const action = form.elements["action"].value;

            if (action === "details") {
                validate_username(form.username, errors);
                validate_email(form.email, errors);
            } else if (action === "password") {
                validate_password(form.current_password, errors);
                validate_password(form.new_password, errors);
                validate_passwords_match(form.new_password, form.confirm_password, errors);
            }

            return show_validation_errors(errors);
        }
    </script>

    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>