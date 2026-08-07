<?php
// public_html/project/admin/list_guides.php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

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

    $guides = selectAll(
        "SELECT id, title, excerpt, game, primary_category, status,
                summary, source_author, source_url, video,
                player_race, opponent_race, matchup,
                IF(api_id IS NULL, 'Manual', 'API') AS source
         FROM Guides
         $where
         ORDER BY $order_by, id ASC
         LIMIT $limit",
        $params
    );
} catch (Throwable $e) {
    error_log("Guide list failed: " . $e->getMessage());
    flash("Guides could not be loaded.", "danger");
}

$shown_count = count($guides);

$guide_columns = [
    "title" => "Title",
    "primary_category" => "Category",
    "player_race" => "Player",
    "opponent_race" => "Opponent",
    "source" => "Source",
];

$guide_actions = [
    ["label" => "View", "url" => "guide.php", "variant" => "primary"],
    ["label" => "Edit", "url" => "admin/edit_guide.php", "variant" => "warning"],
    [
        "label" => "Delete",
        "url" => "admin/delete_guide.php",
        "method" => "POST",
        "include_parameter_in_url" => true,
        "query_parameters" => ["return_to" => "admin/list_guides.php"],
        "variant" => "danger",
    ],
];
?>
<!doctype html>
<html lang="en">

<head><?php render_head("Manage StarCraft Guides"); ?></head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Manage StarCraft Guides</h1>
        <?php render_guide_search(
            $filters,
            $limit,
            $sort,
            $direction,
            $sort_options
        ); ?>
        <?php
        render_result_summary($shown_count, $matching_count);
        render_table(
            $guides,
            $guide_columns,
            $guide_actions,
            "No guides matched the selected filters."
        ); ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>