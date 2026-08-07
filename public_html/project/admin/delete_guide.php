<?php
// public_html/project/admin/delete_guide.php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$return_to = safe_project_return_url(
    $_GET["return_to"] ?? "",
    [
        "guides.php",
        "my_guides.php",
        "profile.php",
        "admin/list_guides.php",
        "admin/guide_associations.php",
        "admin/unassociated_guides.php",
    ],
    "admin/list_guides.php"
);

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
require_csrf_token($return_to);
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