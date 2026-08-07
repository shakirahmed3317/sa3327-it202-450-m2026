<?php
// public_html/project/admin/list_guides.php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$sort_options = [
    "modified" => "Date Saved",
    "username" => "Username",
    "title" => "Title",
    "primary_category" => "Category",
    "game" => "Game",
    "player_race" => "Player Race",
    "opponent_race" => "Opponent Race",
];
$sort_column_map = [
    "modified" => "ug.modified",
    "username" => "u.username",
    "title" => "g.title",
    "primary_category" => "g.primary_category",
    "game" => "g.game",
    "player_race" => "g.player_race",
    "opponent_race" => "g.opponent_race",
];

$association_filter_rules = guide_filter_rules();
array_unshift($association_filter_rules, "username");

$list_config = [
    "filters" => $association_filter_rules,
    "sort_columns" => $sort_column_map,
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

$from = "FROM UserGuides ug
    JOIN Users u ON u.id = ug.user_id
    JOIN Guides g ON g.id = ug.guide_id";

$where = "WHERE ug.is_active = 1";

// Reuse all Guide filters, then add the relationship-specific username filter.
$filter_query = build_guide_filter_query($filters, "g.");
if (!empty($filter_query["sql"])) {
    $where .= " AND " . $filter_query["sql"];
}
$params = $filter_query["params"];

$params = $filter_query["params"];
if (!empty($filters["username"])) {
    $where .= " AND u.username LIKE :username";
    $params["username"] = "%" . $filters["username"] . "%";
}

$matching_count = 0;
$total_pages = 1;
$relationships  = [];

try {
    // The count uses the same filters but no ORDER BY or LIMIT.
    $count_row = select(
        "SELECT COUNT(*) AS total
         $from
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
    $relationships = selectAll(
        "SELECT ug.modified AS saved_on,
                u.id AS user_id, u.username,
                g.id AS guide_id, g.title, g.primary_category,
                g.player_race, g.opponent_race,
                IF(g.api_id IS NULL, 'Manual', 'API') AS source
         $from
         $where
         ORDER BY $order_by, ug.user_id ASC, ug.guide_id ASC
         LIMIT :limit OFFSET :offset",
        $list_params
    );
} catch (Throwable $e) {
    error_log("Guide association list failed: " . $e->getMessage());
    flash("Guide associations could not be loaded.", "danger");
}

$shown_count = count($relationships);

$relationship_columns = [
    "title" => "Title",
    "primary_category" => "Category",
    "player_race" => "Player",
    "opponent_race" => "Opponent",
    "source" => "Source",
    "username" => "User",
    "saved_on" => "Saved",
];

$return_to = current_project_request_url("admin/guide_associations.php");
$relationship_actions = [
    [
        "label" => "View",
        "url" => "guide.php",
        "row_key" => "guide_id",
        "parameter" => "id",
        "query_parameters" => ["return_to" => $return_to],
        "variant" => "primary",
    ],
    [
        "label" => "View User",
        "url" => "profile.php",
        "row_key" => "user_id",
        "parameter" => "id",
        "query_parameters" => ["return_to" => $return_to],
        "variant" => "secondary",
    ],
    [
        "label" => "Remove",
        "url" => "admin/remove_guide_association.php",
        "method" => "POST",
        "row_parameters" => [
            "user_id" => "user_id",
            "guide_id" => "guide_id",
        ],
        "query_parameters" => ["return_to" => $return_to],
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
            $relationships,
            $relationship_columns,
            $relationship_actions,
            "No relationships matched these filters."
        );
        $query_params = array_merge($filters, [
            "sort" => $sort,
            "direction" => $direction,
            "limit" => $limit,
        ]);
        render_pagination(
            $page,
            $total_pages,
            $query_params
        );
        ?>

    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>