<?php
// public_html/project/guides.php
require_once(__DIR__ . "/../../lib/app.php");

$sort_options = [
    "modified" => "Recently Updated",
    "title" => "Title",
    "primary_category" => "Category",
    "game" => "Game",
    "player_race" => "Player Race",
    "opponent_race" => "Opponent Race",
];

$allowed_sort_columns = array_keys($sort_options);
$list_config = [
    "filters" => guide_filter_rules(),
    // Each submitted sort key matches its trusted SQL column on these pages.
    "sort_columns" => $allowed_sort_columns,
];

$list_state = build_list_query_state($_GET, $list_config);
$filters = $list_state["filters"];
$sort = $list_state["sort"];
$direction = $list_state["direction"];
$order_by = $list_state["order_by"];
$limit = $list_state["limit"];

$filter_query = build_guide_filter_query($filters);
$where = "";
if (!empty($filter_query["sql"])) {
    $where = "WHERE " . $filter_query["sql"];
}
$params = $filter_query["params"];

$matching_count = 0;
$guides = [];

try {
    // The count uses the same filters but no ORDER BY or LIMIT.
    $count_row = select(
        "SELECT COUNT(*) AS total
         FROM Guides
         $where
         LIMIT 1",
        $params
    );
    $matching_count = (int) ($count_row["total"] ?? 0);

    $user_id = get_user_id(); // returns 0 when not logged in
    $list_params = array_merge($params, ["user_id" => $user_id]);

    $guides = selectAll(
        "SELECT g.id, g.title, g.excerpt, g.game, g.primary_category,
            g.status, g.summary, g.source_author, g.source_url, g.video,
            g.player_race, g.opponent_race, g.matchup,
            IF(g.api_id IS NULL, 'Manual', 'API') AS source,
            EXISTS (
                SELECT 1
                FROM UserGuides ug
                WHERE ug.guide_id = g.id
                  AND ug.user_id = :user_id
                  AND ug.is_active = 1
            ) AS is_saved
     FROM Guides g
     $where
     ORDER BY $order_by, g.id ASC
     LIMIT $limit",
        $list_params
    );
} catch (Throwable $e) {
    error_log("Guide list failed: " . $e->getMessage());
    flash("Guides could not be loaded.", "danger");
}

$shown_count = count($guides);

?>
<!doctype html>
<html lang="en">

<head><?php render_head("StarCraft Guides"); ?></head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>StarCraft Guides</h1>
        <?php render_guide_search(
            $filters,
            $limit,
            $sort,
            $direction,
            $sort_options
        ); ?>
        <?php
        render_result_summary($shown_count, $matching_count);
        render_grid($guides); ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>