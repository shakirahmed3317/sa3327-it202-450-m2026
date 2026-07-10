<?php
// File: partials/nav.php
$isLoggedIn = is_logged_in();
?>
<link rel="stylesheet" href="/project/styles.css">
<nav>
    <ul>
        <li><a href="/project/index.php">Home</a></li>
        <?php if ($isLoggedIn): ?>
            <li><a href="/project/dashboard.php">Dashboard</a></li>
            <li><a href="/project/logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="/project/login.php">Login</a></li>
            <li><a href="/project/register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
<script src="/project/helpers.js"></script>
