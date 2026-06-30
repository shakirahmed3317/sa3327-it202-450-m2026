<?php
// Temporary: a later lesson will move this check into shared auth utilities.
$isLoggedIn = isset($_SESSION["user"]);
?>
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
