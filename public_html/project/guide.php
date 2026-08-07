<?php
// public_html/project/guide.php
require_once(__DIR__ . "/../../lib/app.php");

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    flash("Missing guide id.", "danger");
    header("Location: " . project_url("guides.php"));
    exit;
}
$user_id = get_user_id(); // returns 0 when not logged in

$guide = null;
try {
    $guide = select(
        "SELECT g.id, g.api_id, g.excerpt, g.game, g.primary_category,
                g.slug, g.source_author, g.source_url, g.status, g.summary,
                g.title, g.video, g.opponent_race, g.player_race, g.matchup,
                g.created, g.modified,
                EXISTS (
                    SELECT 1
                    FROM UserGuides ug
                    WHERE ug.guide_id = g.id
                      AND ug.user_id = :user_id
                      AND ug.is_active = 1
                ) AS is_saved
         FROM Guides g
         WHERE g.id = :id
         LIMIT 1",
        ["id" => $id, "user_id" => $user_id]
    );
} catch (Throwable $e) {
    error_log("Guide lookup failed: " . $e->getMessage());
    flash("The guide could not be loaded.", "danger");
    header("Location: " . project_url("guides.php"));
    exit;
}

if ($guide === null) {
    flash("Guide not found.", "warning");
    header("Location: " . project_url("guides.php"));
    exit;
}
?>
<!doctype html>
<html lang="en">

<head><?php render_head($guide["title"]); ?></head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <?php
        $card_options = [
            "show_detail_view" => true,
        ];
        render_guide_card($guide, $card_options);
        ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>