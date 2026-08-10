<?php
// public_html/project/internal/toggle_saved_guide.php
/**
 * Usage contract:
 * - Accepts POST requests from the Save/Remove form in partials/guide_card.php.
 * - Requires a logged-in user.
 * - Expects guide_id as a positive integer and new_is_saved as 0 or 1.
 * - Accepts an optional local return_to path and query string for an approved page.
 * - Updates only the current session user's relationship, then flashes and redirects.
 */
require_once(__DIR__ . "/../../../lib/app.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . project_url("guides.php"));
    exit;
}

if (!is_logged_in()) {
    flash("Log in before saving a guide.", "warning");
    header("Location: " . project_url("login.php"));
    exit;
}

$return_url = safe_project_return_url(
    $_POST["return_to"] ?? "",
    ["guides.php", "my_guides.php", "guide.php", "profile.php"],
    "guides.php"
);
require_csrf_token($return_url);
$guide_id = filter_input(INPUT_POST, "guide_id", FILTER_VALIDATE_INT);
if (!$guide_id) {
    flash("Choose a valid guide.", "warning");
    header("Location: " . $return_url);
    exit;
}

$new_is_saved = filter_input(
    INPUT_POST,
    "new_is_saved",
    FILTER_VALIDATE_INT
);
if (!in_array($new_is_saved, [0, 1], true)) {
    flash("Choose a valid saved-guide action.", "warning");
    header("Location: " . $return_url);
    exit;
}

$user_id = get_user_id();

try {
    insert(
        "UserGuides",
        [
            "user_id" => $user_id,
            "guide_id" => $guide_id,
            "is_active" => $new_is_saved,
        ],
        [
            "update_duplicate" => true,
            "columns_to_update" => ["is_active"],
        ]
    );
    if ($new_is_saved === 1) {
        flash("Guide saved.", "success");
    } else {
        flash("Guide removed from your saved list.", "success");
    }
} catch (Throwable $e) {
    error_log("Saved guide toggle failed: " . $e->getMessage());
    flash("The saved guide could not be updated.", "danger");
}

header("Location: " . $return_url);
exit;