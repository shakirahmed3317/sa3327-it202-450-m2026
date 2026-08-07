<?php
// public_html/project/admin/edit_guide.php
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
        "guide.php",
    ],
    "admin/list_guides.php"
);

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    flash("Missing guide id.", "danger");
    header("Location: $return_to");
    exit;
}

$errors = [];
$guide = null;
if (isset($_POST["action"]) && $_POST["action"] === "update_guide") {
    require_csrf_token($return_to);
    $title = "";
    $primary_category = "";
    $player_race = "";
    $opponent_race = "";
    $summary = "";
    $source_url = "";
    if (isset($_POST["title"]) && is_string($_POST["title"])) {
        $title = trim($_POST["title"]);
    }
    if (isset($_POST["primary_category"]) && is_string($_POST["primary_category"])) {
        $primary_category = trim($_POST["primary_category"]);
    }
    if (isset($_POST["player_race"]) && is_string($_POST["player_race"])) {
        $player_race = trim($_POST["player_race"]);
    }
    if (isset($_POST["opponent_race"]) && is_string($_POST["opponent_race"])) {
        $opponent_race = trim($_POST["opponent_race"]);
    }
    if (isset($_POST["summary"]) && is_string($_POST["summary"])) {
        $summary = trim($_POST["summary"]);
    }
    if (isset($_POST["source_url"]) && is_string($_POST["source_url"])) {
        $source_url = trim($_POST["source_url"]);
    }

    if ($title === "" || strlen($title) > 150) {
        $errors[] = "Title is required and must be 150 characters or less.";
    }
    if ($primary_category === "" || strlen($primary_category) > 50) {
        $errors[] = "Primary category is required and must be 50 characters or less.";
    }
    $valid_races = ["any", "terran", "protoss", "zerg"];
    if (!in_array($player_race, $valid_races, true)) {
        $errors[] = "Choose a valid player race.";
    }
    if (!in_array($opponent_race, $valid_races, true)) {
        $errors[] = "Choose a valid opponent race.";
    }
    if ($summary === "") {
        $errors[] = "Summary is required.";
    }
    $source_url_value = null;
    if ($source_url !== "") {
        $source_url_value = sc_nullable_url($source_url);
        if ($source_url_value === null) {
            $errors[] = "Source URL must be a valid HTTP or HTTPS URL.";
        }
    }

    if (empty($errors)) {
        // Keep matchup aligned with the editable race fields.
        $matchup = null;
        if ($player_race !== "any" && $opponent_race !== "any") {
            $matchup = $player_race[0] . "v" . $opponent_race[0];
        }

        try {
            update("Guides", [
                "id" => $id,
                "title" => $title,
                "primary_category" => $primary_category,
                "player_race" => $player_race,
                "opponent_race" => $opponent_race,
                "matchup" => $matchup,
                "summary" => $summary,
                "source_url" => $source_url_value,
            ]);
            flash("Guide updated.", "success");
            $edit_url = project_url("admin/edit_guide.php") . "?" . http_build_query([
                "id" => $id,
                "return_to" => $return_to,
            ]);
            header("Location: " . $edit_url);
            exit;
        } catch (Throwable $e) {
            error_log("Guide update failed: " . $e->getMessage());
            $errors[] = "The guide could not be updated.";
        }
    }

    // Keep safe submitted values visible after validation errors.
    $guide = compact(
        "id",
        "title",
        "primary_category",
        "player_race",
        "opponent_race",
        "summary",
        "source_url"
    );
}

try {
    $guide = select("SELECT id, title, primary_category, player_race, opponent_race, summary, source_url FROM Guides WHERE id = :id LIMIT 1", ["id" => $id]);
} catch (Throwable $e) {
    error_log("Guide lookup failed: " . $e->getMessage());
    flash("The guide could not be loaded.", "danger");
    header("Location: $return_to");
    exit;
}

if ($guide === null) {
    flash("Guide not found.", "warning");
    header("Location: $return_to");
    exit;
}

flash_errors($errors);
?>
<!-- Keep the shared head, nav, flash, and scripts calls from the standard page shell. -->
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Edit Guide: " . htmlspecialchars($guide["title"])); ?>
</head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Edit Guide</h1>
        <form method="post">
            <?php
            render_csrf_input();
            render_input([
                "name" => "title",
                "value" => $guide["title"],
                "attributes" => ["required" => true, "maxlength" => 150],
            ]);
            render_input([
                "label" => "Primary Category",
                "name" => "primary_category",
                "value" => $guide["primary_category"],
                "attributes" => ["required" => true, "maxlength" => 50],
            ]);
            render_input([
                "type" => "select",
                "name" => "player_race",
                "value" => $guide["player_race"],
                "options" => ["any" => "Any", "terran" => "Terran", "protoss" => "Protoss", "zerg" => "Zerg"],
            ]);
            render_input([
                "type" => "select",
                "name" => "opponent_race",
                "value" => $guide["opponent_race"],
                "options" => ["any" => "Any", "terran" => "Terran", "protoss" => "Protoss", "zerg" => "Zerg"],
            ]);
            render_input([
                "type" => "textarea",
                "name" => "summary",
                "value" => $guide["summary"],
                "attributes" => ["required" => true, "rows" => 5],
            ]);
            render_input([
                "label" => "Source URL (optional)",
                "type" => "url",
                "name" => "source_url",
                "value" => $guide["source_url"],
                "attributes" => ["maxlength" => 500],
            ]);
            render_button([
                "text" => "Update Guide",
                "variant" => "warning",
                "attributes" => ["name" => "action", "value" => "update_guide"],
            ]);

            ?>
            <a class="btn btn-secondary"
                href="<?php echo htmlspecialchars($return_to); ?>">Back</a>
        </form>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>