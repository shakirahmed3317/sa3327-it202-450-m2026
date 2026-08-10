<?php
// public_html/project/internal/clear_saved_guides.php
require_once(__DIR__ . "/../../../lib/app.php");

require_csrf_token(project_url("my_guides.php"));
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash("That saved-guide action is not available.", "warning");
    header("Location: " . project_url("my_guides.php"));
    exit;
}

if (!is_logged_in()) {
    flash("Log in to update saved guides.", "warning");
    header("Location: " . project_url("login.php"));
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare(
        "UPDATE UserGuides
         SET is_active = 0
         WHERE user_id = :user_id AND is_active = 1"
    );
    $stmt->execute([":user_id" => get_user_id()]);
    flash("All saved guides were removed.", "success");
} catch (Throwable $e) {
    error_log("Clear saved guides failed: " . $e->getMessage());
    flash("Saved guides could not be cleared.", "danger");
}

header("Location: " . project_url("my_guides.php"));
exit;