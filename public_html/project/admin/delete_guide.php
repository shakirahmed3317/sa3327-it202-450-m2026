<?php
// public_html/project/admin/delete_guide.php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$allowed_return_pages = [
    "guides.php" => project_url("guides.php"),
    "admin/list_guides.php" => project_url("admin/list_guides.php"),
];
$return_to = $allowed_return_pages["guides.php"];
if (isset($_GET["return_to"]) && is_string($_GET["return_to"])) {
    $requested_return_to = $_GET["return_to"];
    if (isset($allowed_return_pages[$requested_return_to])) {
        $return_to = $allowed_return_pages[$requested_return_to];
    }
}

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    flash("Missing guide id.", "danger");
    header("Location: " . $return_to);
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash("Use the guide list to delete a guide.", "warning");
    header("Location: " . $return_to);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM Guides WHERE id = :id LIMIT 1");
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() === 1) {
        flash("Guide deleted.", "success");
    } else {
        flash("Guide not found.", "warning");
    }
} catch (PDOException $e) {
    error_log("Guide deletion failed: " . $e->getMessage());
    flash("The guide could not be deleted.", "danger");
}

header("Location: " . $return_to);
exit;
