<?php
require_once(__DIR__ . "/../../lib/app.php");

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}
// Temporary debugging: check the server log to see the current session user shape.
error_log("Dashboard session user: " . var_export($_SESSION["user"] ?? [], true));
?>
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Dashboard"); ?>

</head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">

        <h1>Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars(get_user_email()); ?></p>
        <!-- Last PHP inside <body> so it captures messages queued during this request. -->
    </main>
    <?php render_flash_messages(); ?>
<?php render_scripts(); ?>
</body>

</html>