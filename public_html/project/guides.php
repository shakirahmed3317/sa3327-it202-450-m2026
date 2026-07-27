<?php
// public_html/project/guides.php
require_once(__DIR__ . "/../../lib/app.php");

$filters = [
    "title" => "",
    "primary_category" => "",
    "game" => "",
    "player_race" => "",
    "opponent_race" => "",
];
// Read only the expected text filters from the query string.
foreach ($filters as $name => $value) {
    if (isset($_GET[$name]) && is_string($_GET[$name])) {
        $filters[$name] = trim($_GET[$name]);
    }
}
// Race filters come from controlled select options, not free-text search fields.
$valid_races = ["", "protoss", "zerg", "terran"];
foreach (["player_race", "opponent_race"] as $name) {
    if (!in_array($filters[$name], $valid_races, true)) {
        $filters[$name] = "";
    }
}

$limit = 10;
if (isset($_GET["limit"]) && is_string($_GET["limit"])) {
    $requested_limit = filter_var(
        $_GET["limit"],
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1, "max_range" => 100]]
    );
    if ($requested_limit !== false) {
        $limit = $requested_limit;
    }
}

$where_parts = [];
$params = [];
if (!empty($filters["title"])) {
    $where_parts[] = "title LIKE :title";
    $params["title"] = "%" . $filters["title"] . "%";
}
if (!empty($filters["primary_category"])) {
    $where_parts[] = "primary_category LIKE :primary_category";
    $params["primary_category"] = "%" . $filters["primary_category"] . "%";
}
if (!empty($filters["game"])) {
    $where_parts[] = "game LIKE :game";
    $params["game"] = "%" . $filters["game"] . "%";
}
if (!empty($filters["player_race"])) {
    $where_parts[] = "LOWER(player_race) = :player_race";
    $params["player_race"] = $filters["player_race"];
}
if (!empty($filters["opponent_race"])) {
    $where_parts[] = "LOWER(opponent_race) = :opponent_race";
    $params["opponent_race"] = $filters["opponent_race"];
}

$where = "";
if ($where_parts) {
    // Every populated field narrows the results.
    $where = "WHERE " . implode(" AND ", $where_parts);
}

$guides = [];
try {
    $guides = selectAll(
        "SELECT id, title, primary_category, player_race, opponent_race, matchup,
                IF(api_id IS NULL, 'Manual', 'API') AS source
         FROM Guides
         $where
         ORDER BY modified DESC
         LIMIT $limit",
        $params
    );
} catch (Throwable $e) {
    error_log("Guide list failed: " . $e->getMessage());
    flash("Guides could not be loaded.", "danger");
}

// Define the visible table columns before the page renders them.
$guide_columns = [
    "title" => "Title",
    "primary_category" => "Category",
    "player_race" => "Player",
    "opponent_race" => "Opponent",
    "source" => "Source",
];

// View is public. Only an Admin receives management actions.
$guide_actions = [["label" => "View", "url" => "guide.php", "variant" => "primary"]];
if (has_role("Admin")) {
    $guide_actions[] = ["label" => "Edit", "url" => "admin/edit_guide.php", "variant" => "warning"];
    $guide_actions[] = [
        "label" => "Delete",
        "url" => "admin/delete_guide.php",
        "method" => "POST",
        "include_parameter_in_url" => true,
        "query_parameters" => ["return_to" => "guides.php"],
        "variant" => "danger",
    ];
}
?>
<!doctype html>
<html lang="en">
<head><?php render_head("StarCraft Guides"); ?></head>
<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>StarCraft Guides</h1>
        <?php render_guide_search($filters, $limit); ?>
        <?php render_table(
            $guides,
            $guide_columns,
            $guide_actions,
            "No guides have been saved yet."
        ); ?>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>
</html>
