<?php
// public_html/project/admin/create_guide.php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$errors = [];
$title = "";
$primary_category = "";
$player_race = "any";
$opponent_race = "any";
$summary = "";
$source_url = "";
$game = "sc2";
$action = "";
if (isset($_POST["action"]) && is_string($_POST["action"])) {
    require_csrf_token(project_url("admin/create_guide.php"));
    $action = $_POST["action"];
}
$active_form = "api";
if ($action === "create_guide") {
    // Keep the manual form visible when its validation returns an error.
    $active_form = "manual";
}
if (isset($_POST["game"]) && is_string($_POST["game"])) {
    $game = strtolower(trim($_POST["game"]));
}

if ($action === "import_guides") {
    try {
        $guides = fetch_starcraft_guides($game, $errors);
        if (empty($errors) && !empty($guides)) {
            $result = insert("Guides", $guides, [
                "update_duplicate" => true,
                "columns_to_update" => [
                    "excerpt",
                    "game",
                    "primary_category",
                    "slug",
                    "source_author",
                    "source_url",
                    "status",
                    "summary",
                    "title",
                    "video",
                    "opponent_race",
                    "player_race",
                    "matchup",
                ],
            ]);
            flash($result["rowCount"] . " API guide row(s) saved.", "success");
            header("Location: " . project_url("admin/create_guide.php"));
            exit;
        }
    } catch (Throwable $e) {
        error_log("Guide import failed: " . $e->getMessage());
        $errors[] = "The guides could not be imported.";
    }
}

if ($action === "create_guide") {
    $supported_games = ["sc1", "sc2"];
    if (!in_array($game, $supported_games, true)) {
        $errors[] = "Choose a supported StarCraft game.";
    }
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
        // Build a matchup only when both selected races are specific.
        $matchup = null;
        if ($player_race !== "any" && $opponent_race !== "any") {
            $matchup = $player_race[0] . "v" . $opponent_race[0];
        }

        try {
            insert("Guides", [
                "api_id" => null,
                "excerpt" => null,
                "game" => $game,
                "primary_category" => $primary_category,
                "slug" => null,
                "source_author" => null,
                "source_url" => $source_url_value,
                "status" => "active",
                "summary" => $summary,
                "title" => $title,
                "video" => null,
                "opponent_race" => $opponent_race,
                "player_race" => $player_race,
                "matchup" => $matchup,
            ]);
            flash("Guide created.", "success");
            header("Location: " . project_url("admin/create_guide.php"));
            exit;
        } catch (Throwable $e) {
            error_log("Manual guide creation failed: " . $e->getMessage());
            $errors[] = "The guide could not be created.";
        }
    }
}

flash_errors($errors);
// Continue with the standard page shell and form block shown beside this snippet.
?>
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Create Guide"); ?>
</head>

<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <!-- Place this block after the Create Guide heading inside the standard page shell. -->
        <div class="btn-group mb-4" role="group" aria-label="Guide creation method">
            <button
                class="btn btn-outline-primary"
                type="button"
                data-guide-form="api"
                aria-controls="guideApiForm">
                Import From API
            </button>
            <button
                class="btn btn-outline-primary"
                type="button"
                data-guide-form="manual"
                aria-controls="guideManualForm">
                Create Manually
            </button>
        </div>

        <div class="row g-4">
            <section
                id="guideApiForm"
                class="col-12"
                <?php if ($active_form !== "api"): ?>hidden<?php endif; ?>>
                <h2>Import API Guides</h2>
                <p>Call getGameGuides() for one StarCraft game and update matching API rows.</p>
                <form method="post">
                    <?php
                    render_csrf_input();
                    render_input([
                        "label" => "Game",
                        "type" => "select",
                        "name" => "game",
                        "value" => $game,
                        "options" => ["sc1" => "StarCraft", "sc2" => "StarCraft II"],
                    ]);
                    render_button([
                        "text" => "Import API Guides",
                        "variant" => "success",
                        "attributes" => ["name" => "action", "value" => "import_guides"],
                    ]);
                    ?>
                </form>
            </section>

            <section
                id="guideManualForm"
                class="col-12"
                <?php if ($active_form !== "manual"): ?>hidden<?php endif; ?>>
                <h2>Create Manual Guide</h2>
                <form method="post">
                    <?php
                    render_csrf_input();
                    render_input([
                        "name" => "title",
                        "value" => $title,
                        "attributes" => ["required" => true, "maxlength" => 150],
                    ]);
                    render_input([
                        "label" => "Primary Category",
                        "name" => "primary_category",
                        "value" => $primary_category,
                        "attributes" => ["required" => true, "maxlength" => 50],
                    ]);
                    render_input([
                        "label" => "Game",
                        "type" => "select",
                        "name" => "game",
                        "value" => $game,
                        "options" => ["sc1" => "StarCraft", "sc2" => "StarCraft II"],
                    ]);
                    render_input([
                        "type" => "select",
                        "name" => "player_race",
                        "value" => $player_race,
                        "options" => ["any" => "Any", "terran" => "Terran", "protoss" => "Protoss", "zerg" => "Zerg"],
                    ]);
                    render_input([
                        "type" => "select",
                        "name" => "opponent_race",
                        "value" => $opponent_race,
                        "options" => ["any" => "Any", "terran" => "Terran", "protoss" => "Protoss", "zerg" => "Zerg"],
                    ]);
                    render_input([
                        "type" => "textarea",
                        "name" => "summary",
                        "value" => $summary,
                        "attributes" => ["required" => true, "rows" => 5],
                    ]);
                    render_input([
                        "label" => "Source URL (optional)",
                        "type" => "url",
                        "name" => "source_url",
                        "value" => $source_url,
                        "attributes" => ["maxlength" => 500],
                    ]);
                    render_button([
                        "text" => "Create Manual Guide",
                        "attributes" => ["name" => "action", "value" => "create_guide"],
                    ]);
                    ?>
                </form>
            </section>
        </div>

        <script>
            const apiGuideForm = document.querySelector("#guideApiForm");
            const manualGuideForm = document.querySelector("#guideManualForm");
            const guideFormButtons = document.querySelectorAll("[data-guide-form]");

            // Keep only the selected workflow visible without affecting either form submission.
            function showGuideForm(formName) {
                const showApiForm = formName === "api";
                apiGuideForm.hidden = !showApiForm;
                manualGuideForm.hidden = showApiForm;

                guideFormButtons.forEach((button) => {
                    const isSelected = button.dataset.guideForm === formName;
                    button.classList.toggle("btn-primary", isSelected);
                    button.classList.toggle("btn-outline-primary", !isSelected);
                    button.setAttribute("aria-pressed", String(isSelected));
                });
            }

            guideFormButtons.forEach((button) => {
                button.addEventListener("click", () => {
                    showGuideForm(button.dataset.guideForm);
                });
            });

            showGuideForm("<?php echo htmlspecialchars($active_form); ?>");
        </script>

    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>

</html>