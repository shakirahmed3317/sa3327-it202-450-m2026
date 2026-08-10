<?php
require_once(__DIR__ . "/../../lib/app.php");
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . project_url("dashboard.php"));
    exit;
}
require_csrf_token(project_url("dashboard.php"));
session_unset();
session_destroy();

header("Location: login.php");
exit;