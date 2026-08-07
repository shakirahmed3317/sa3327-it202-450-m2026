<?php
// public_html/project/admin/assign_guides.php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$db = getDB();
$usernameSearch = trim((string) ($_GET["username"] ?? $_POST["username"] ?? ""));
$guideSearch = trim((string) ($_GET["guide"] ?? $_POST["guide"] ?? ""));
$users = [];
$guides = [];

if (isset($_POST["action"]) && $_POST["action"] === "toggle_associations") {
    require_csrf_token(project_url($redirect));
    $submittedUserIds = $_POST["user_ids"] ?? [];
    $submittedGuideIds = $_POST["guide_ids"] ?? [];
    if (!is_array($submittedUserIds) || !is_array($submittedGuideIds)) {
        $submittedUserIds = [];
        $submittedGuideIds = [];
    }

    // Convert submitted checkbox values to unique positive integer IDs.
    $userIds = array_unique(array_filter(
        array_map("intval", $submittedUserIds),
        fn($id) => $id > 0
    ));
    $guideIds = array_unique(array_filter(
        array_map("intval", $submittedGuideIds),
        fn($id) => $id > 0
    ));

    // Match the maximum number of results shown in each search group.
    $userIds = array_slice(array_values($userIds), 0, 25);
    $guideIds = array_slice(array_values($guideIds), 0, 25);

    if (empty($userIds) || empty($guideIds)) {
        flash("Select at least one user and one guide.", "warning");
    } else {
        try {
            $stmt = $db->prepare(
                "INSERT INTO UserGuides (user_id, guide_id, is_active)
                 VALUES (:user_id, :guide_id, 1)
                 ON DUPLICATE KEY UPDATE
                    is_active = IF(is_active = 1, 0, 1)"
            );
            $toggledCount = 0;
            $failedCount = 0;

            foreach ($userIds as $userId) {
                foreach ($guideIds as $guideId) {
                    try {
                        $stmt->execute([
                            ":user_id" => $userId,
                            ":guide_id" => $guideId,
                        ]);
                        $toggledCount++;
                    } catch (PDOException $e) {
                        $failedCount++;
                        error_log(
                            "Guide association toggle failed for user $userId"
                                . " and guide $guideId: " . $e->getMessage()
                        );
                    }
                }
            }

            if ($toggledCount > 0) {
                flash("$toggledCount selected user-guide pair(s) were toggled.", "success");
            }

            if ($failedCount > 0) {
                flash("$failedCount selected pair(s) could not be updated.", "warning");
            }
        } catch (PDOException $e) {
            error_log("Guide association setup failed: " . $e->getMessage());
            flash("Could not update guide associations.", "danger");
        }
    }

    $returnParams = array_filter(
        ["username" => $usernameSearch, "guide" => $guideSearch],
        fn($value) => $value !== ""
    );
    $redirect = "admin/assign_guides.php";
    if (!empty($returnParams)) {
        $redirect .= "?" . http_build_query($returnParams);
    }

    header("Location: " . project_url($redirect));
    exit;
}

try {
    if (!empty($usernameSearch)) {
        $users = selectAll(
            "SELECT u.id, u.username,
                    GROUP_CONCAT(
                        g.title ORDER BY g.title SEPARATOR '|||'
                    ) AS associated_guide_titles
             FROM Users u
             LEFT JOIN UserGuides ug
                ON ug.user_id = u.id
                AND ug.is_active = 1
             LEFT JOIN Guides g ON g.id = ug.guide_id
             WHERE u.username LIKE :username
             GROUP BY u.id, u.username
             ORDER BY u.username ASC
             LIMIT 25",
            ["username" => "%$usernameSearch%"]
        );
    }

    if (!empty($guideSearch)) {
        $guides = selectAll(
            "SELECT id, title, primary_category
             FROM Guides
             WHERE title LIKE :title
             ORDER BY title ASC
             LIMIT 25",
            ["title" => "%$guideSearch%"]
        );
    }
} catch (Throwable $e) {
    error_log("Association search failed: " . $e->getMessage());
    flash("Association choices could not be loaded.", "danger");
}
?>
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Assign Guides"); ?>
</head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Assign Guides</h1>
        <form method="get" class="row g-3 align-items-end mb-4">
            <div class="col-md-5">
                <?php render_input([
                    "label" => "Username Search",
                    "name" => "username",
                    "value" => $usernameSearch,
                ]); ?>
            </div>
            <div class="col-md-5">
                <?php render_input([
                    "label" => "Guide Title Search",
                    "name" => "guide",
                    "value" => $guideSearch,
                ]); ?>
            </div>
            <div class="col-md-2">
                <?php render_button(["text" => "Search", "type" => "submit"]); ?>
            </div>
        </form>
        <form id="toggleForm" method="post">
            <?php
            render_csrf_input();
            render_input([
                "type" => "hidden",
                "name" => "action",
                "value" => "toggle_associations",
            ]);
            render_input([
                "type" => "hidden",
                "name" => "username",
                "value" => $usernameSearch,
            ]);
            render_input([
                "type" => "hidden",
                "name" => "guide",
                "value" => $guideSearch,
            ]);
            ?>
        </form>

        <div class="row g-4">
            <fieldset class="col-md-6">
                <legend class="h2">Users</legend>
                <?php if (empty($users)): ?>
                    <p>No users match this search.</p>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <?php
                    // GROUP_CONCAT() uses ||| so each title can become one list item.
                    $associatedTitles = array_filter(explode(
                        "|||",
                        (string) ($user["associated_guide_titles"] ?? "")
                    ));
                    ?>
                    <div class="list-group-item mb-3 border rounded">
                        <?php render_input([
                            "type" => "checkbox",
                            "id" => "user_" . (int) $user["id"],
                            "name" => "user_ids[]",
                            "value" => (int) $user["id"],
                            "label" => (string) $user["username"],
                            "wrapperClass" => "mb-2",
                            "attributes" => ["form" => "toggleForm"],
                        ]); ?>

                        <div class="bg-body-tertiary border rounded p-2 overflow-auto"
                            style="max-height: 8rem;">
                            <p class="small fw-semibold mb-1">Currently Associated</p>
                            <?php if (empty($associatedTitles)): ?>
                                <p class="small text-body-secondary mb-0">No active guides</p>
                            <?php else: ?>
                                <ul class="small mb-0 ps-3">
                                    <?php foreach ($associatedTitles as $title): ?>
                                        <li><?php echo htmlspecialchars($title); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </fieldset>

            <fieldset class="col-md-6">
                <legend class="h2">Guides</legend>
                <?php if (empty($guides)): ?>
                    <p>No guides match this search.</p>
                <?php endif; ?>
                <?php foreach ($guides as $guide): ?>
                    <?php render_input([
                        "type" => "checkbox",
                        "id" => "guide_" . (int) $guide["id"],
                        "name" => "guide_ids[]",
                        "value" => (int) $guide["id"],
                        "label" => (string) $guide["title"],
                        "wrapperClass" => "mb-2",
                        "attributes" => ["form" => "toggleForm"],
                    ]); ?>
                <?php endforeach; ?>
            </fieldset>
        </div>

        <?php if (!empty($users) && !empty($guides)): ?>
            <?php render_button([
                "text" => "Toggle Selected Associations",
                "variant" => "warning",
                "attributes" => ["form" => "toggleForm"],
            ]); ?>
        <?php endif; ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>

</body>

</html>