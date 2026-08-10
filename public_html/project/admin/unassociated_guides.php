<?php
// public_html/project/admin/unassociated_guides.php
// Start with a copy of admin/list_guides.php. Replace its complete PHP
// processing block above <!doctype html> with this snippet.
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

// Start with the unchanged list_guides.php filter and sort configuration.
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
    "sort_columns" => $allowed_sort_columns,
];
$list_state = build_list_query_state($_GET, $list_config);
$filters = $list_state["filters"];
$sort = $list_state["sort"];
$direction = $list_state["direction"];
$order_by = $list_state["order_by"];
$limit = $list_state["limit"];

$pagination_state = build_pagination_query_state($_GET, $limit);
$page = $pagination_state["page"];
$offset = $pagination_state["offset"];

$filter_query = build_guide_filter_query($filters);
$where = "WHERE NOT EXISTS (
    SELECT 1
    FROM UserGuides ug
    WHERE ug.guide_id = Guides.id
      AND ug.is_active = 1
)";
if (!empty($filter_query["sql"])) {
    $where .= " AND " . $filter_query["sql"];
}
$params = $filter_query["params"];

$matching_count = 0;
$total_pages = 1;
$guides = [];
try {
    $count_row = select(
        "SELECT COUNT(*) AS total
         FROM Guides
         $where
         LIMIT 1",
        $params
    );
    $matching_count = (int) ($count_row["total"] ?? 0);
    $total_pages = pagination_total_pages($matching_count, $limit);
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = pagination_offset($page, $limit);
    }

    $list_params = array_merge($params, [
        "limit" => $limit,
        "offset" => $offset,
    ]);
    $guides = selectAll(
        "SELECT id, title, excerpt, game, primary_category, status,
                summary, source_author, source_url, video,
                player_race, opponent_race, matchup,
                IF(api_id IS NULL, 'Manual', 'API') AS source
         FROM Guides
         $where
         ORDER BY $order_by, id ASC
         LIMIT :limit OFFSET :offset",
        $list_params
    );
} catch (Throwable $e) {
    error_log("Unassociated guide list failed: " . $e->getMessage());
    flash("Unassociated guides could not be loaded.", "danger");
}
$shown_count = count($guides);

// Keep the existing list_guides.php columns and remove only the Delete action.
$guide_columns = [
    "title" => "Title",
    "primary_category" => "Category",
    "player_race" => "Player",
    "opponent_race" => "Opponent",
    "source" => "Source",
];
$return_to = current_project_request_url("admin/unassociated_guides.php");
$guide_actions = [
    [
        "label" => "View",
        "url" => "guide.php",
        "query_parameters" => ["return_to" => $return_to],
        "variant" => "primary",
    ],
    [
        "label" => "Edit",
        "url" => "admin/edit_guide.php",
        "query_parameters" => ["return_to" => $return_to],
        "variant" => "warning",
    ],
];
?>
<!doctype html>
<html lang="en">

<head><?php render_head("Unassociated Guides"); ?></head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Unassociated Guides</h1>
        <?php render_guide_search(
            $filters,
            $limit,
            $sort,
            $direction,
            $sort_options
        ); ?>
        <?php render_result_summary($shown_count, $matching_count); ?>
        <?php render_table(
            $guides,
            $guide_columns,
            $guide_actions,
            "No unassociated guides matched the selected filters."
        ); ?>
        <?php
        $query_params = array_merge($filters, [
            "sort" => $sort,
            "direction" => $direction,
            "limit" => $limit,
        ]);
        render_pagination($page, $total_pages, $query_params);
        ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>