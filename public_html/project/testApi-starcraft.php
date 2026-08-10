<?php
// public_html/project/testApi-starcraft.php
require_once(__DIR__ . "/../../lib/app.php");

$game = "sc2";
if (isset($_POST["game"]) && is_string($_POST["game"])) {
    $game = strtolower(trim($_POST["game"]));
}

$decoded = null;
$errors = [];
if (isset($_POST["action"]) && $_POST["action"] === "test_guides") {
    $supported_games = ["sc1", "sc2"];
    if (!in_array($game, $supported_games, true)) {
        $errors[] = "Choose a supported StarCraft game.";
    }

    if (empty($errors)) {
        // This API receives the selected game as part of the URL path.
        // No query or body data is needed, so pass an empty array to api_get().
        $result = api_get(
            "https://unofficial-starcraft-guides-api.p.rapidapi.com/api/v1/games/$game/guides/index.json",
            [],
            ["key_name" => "STARCRAFT_API_KEY", "host_name" => "STARCRAFT_API_HOST"]
        );
        $decoded = decode_api_response($result, "guides", $errors);
    }
}

flash_errors($errors);
?>
<!doctype html>
<html lang="en">
<head>
    <?php render_head("Test StarCraft Guides API");?>
</head>
<body style="color: black;">
    <?php render_nav(); ?>
    <main>
        <h1>Test StarCraft Guides API</h1>
        <form method="post">
            <label for="game">Game</label>
            <select id="game" name="game">
                <option value="sc1"<?php if ($game === "sc1"): ?> selected<?php endif; ?>>StarCraft</option>
                <option value="sc2"<?php if ($game === "sc2"): ?> selected<?php endif; ?>>StarCraft II</option>
            </select>
            <button name="action" value="test_guides" type="submit">Test Live API</button>
        </form>

        <pre><?php var_dump($decoded); ?></pre>
    </main>
    <?php render_flash_messages(); ?>
</body>
</html>