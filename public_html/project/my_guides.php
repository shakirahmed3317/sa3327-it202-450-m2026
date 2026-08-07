<?php
// public_html/project/my_guides.php
require_once(__DIR__ . "/../../lib/app.php");

if (!is_logged_in()) {
    flash("Log in to view your saved guides.", "warning");
    header("Location: " . project_url("login.php"));
    exit;
}

$sort_options = [
    "modified" => "Date Saved",
    "title" => "Title",
    "primary_category" => "Category",
    "game" => "Game",
    "player_race" => "Player Race",
    "opponent_race" => "Opponent Race",
];
$sort_column_map = [
    "modified" => "ug.modified",
    "title" => "g.title",
    "primary_category" => "g.primary_category",
    "game" => "g.game",
    "player_race" => "g.player_race",
    "opponent_race" => "g.opponent_race",
];
$list_config = [
    "filters" => guide_filter_rules(),
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

$column_prefix = "g."; // for table names or aliases
$filter_query = build_guide_filter_query($filters, $column_prefix);
$where = "WHERE ug.user_id = :user_id AND ug.is_active = 1";
if (!empty($filter_query["sql"])) {
    $where .= " AND " . $filter_query["sql"];
}
$params = array_merge(
    ["user_id" => get_user_id()],
    $filter_query["params"]
);

$matching_count = 0;
$total_pages = 1;
$guides = [];

try {
    $count_row = select(
        "SELECT COUNT(*) AS total
         FROM UserGuides ug
         JOIN Guides g ON g.id = ug.guide_id
         $where
         LIMIT 1",
        $params
    );
    $matching_count = (int) ($count_row["total"] ?? 0);
    $total_pages = pagination_total_pages($matching_count, $limit);

    // A removal may leave the requested page beyond the new final page.
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = pagination_offset($page, $limit);
    }

    $list_params = array_merge($params, [
        "limit" => $limit,
        "offset" => $offset,
    ]);
    $guides = selectAll(
        "SELECT g.id, g.title, g.excerpt, g.game, g.primary_category,
                g.status, g.summary, g.source_author, g.source_url, g.video,
                g.player_race, g.opponent_race, g.matchup,
                IF(g.api_id IS NULL, 'Manual', 'API') AS source,
                1 AS is_saved,
                ug.modified AS saved_on
         FROM UserGuides ug
         JOIN Guides g ON g.id = ug.guide_id
         $where
         ORDER BY $order_by, g.id ASC
         LIMIT :limit OFFSET :offset",
        $list_params
    );
} catch (Throwable $e) {
    error_log("Saved guide list failed: " . $e->getMessage());
    flash("Saved guides could not be loaded.", "danger");
}
$shown_count = count($guides);
?>
<!doctype html>
<html lang="en">

<head><?php render_head("My Saved Guides"); ?></head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
            <h1>My Saved Guides</h1>
            <?php if ($matching_count > 0): ?>
                <form method="post"
                    action="<?php echo project_url("internal/clear_saved_guides.php"); ?>">
                    <?php render_button([
                        "text" => "Remove All Saved Guides",
                        "variant" => "danger",
                    ]); ?>
                </form>
            <?php endif; ?>
        </div>

        <?php render_guide_search(
            $filters,
            $limit,
            $sort,
            $direction,
            $sort_options
        ); ?>
        <?php render_result_summary($shown_count, $matching_count); ?>
        <?php
        $card_options = [
            "show_saved_on" => true,
        ];
        render_grid(
            $guides,
            $card_options,
            "No saved guides matched the selected filters."
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