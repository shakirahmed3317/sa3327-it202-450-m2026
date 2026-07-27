<?php
// public_html/project/guide.php
require_once(__DIR__ . "/../../lib/app.php");

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    flash("Missing guide id.", "danger");
    header("Location: " . project_url("guides.php"));
    exit;
}

$guide = null;
try {
    $guide = select(
        "SELECT id, api_id, excerpt, game, primary_category, slug,
                source_author, source_url, status, summary, title, video,
                opponent_race, player_race, matchup, created, modified
         FROM Guides
         WHERE id = :id
         LIMIT 1",
        ["id" => $id]
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
$video_url = sc_nullable_url($guide["video"] ?? null);
?>
<!doctype html>
<html lang="en">
<head><?php render_head($guide["title"]); ?></head>
<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <article class="card">
            <div class="card-body">
                <h1 class="card-title"><?php echo htmlspecialchars($guide["title"]); ?></h1>
                <p class="text-body-secondary">
                    <?php echo htmlspecialchars((string) $guide["game"]); ?> |
                    <?php echo htmlspecialchars((string) ($guide["primary_category"] ?? "Uncategorized")); ?> |
                    <?php echo htmlspecialchars((string) ($guide["status"] ?? "Unknown status")); ?>
                </p>

                <dl class="row">
                    <dt class="col-sm-3">Player Race</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars((string) ($guide["player_race"] ?? "Any")); ?></dd>
                    <dt class="col-sm-3">Opponent Race</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars((string) ($guide["opponent_race"] ?? "Any")); ?></dd>
                    <?php if (!empty($guide["matchup"])): ?>
                        <dt class="col-sm-3">Matchup</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars((string) $guide["matchup"]); ?></dd>
                    <?php endif; ?>
                </dl>

                <?php if (!empty($guide["excerpt"])): ?>
                    <p class="lead"><?php echo htmlspecialchars((string) $guide["excerpt"]); ?></p>
                <?php endif; ?>
                <?php if (!empty($guide["summary"])): ?>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars((string) $guide["summary"])); ?></p>
                <?php endif; ?>

                <?php if (!empty($guide["source_url"])): ?>
                    <p>
                        <a href="<?php echo htmlspecialchars((string) $guide["source_url"]); ?>"
                            target="_blank" rel="noopener noreferrer">
                            <?php echo htmlspecialchars((string) ($guide["source_author"] ?? "Original source")); ?>
                        </a>
                    </p>
                <?php endif; ?>
                <?php if ($video_url !== null): ?>
                    <p>
                        <a href="<?php echo htmlspecialchars($video_url); ?>"
                            target="_blank" rel="noopener noreferrer">Watch Video</a>
                    </p>
                <?php endif; ?>
                <a class="btn btn-secondary" href="<?php echo project_url("guides.php"); ?>">Back To Guides</a>
            </div>
        </article>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>
</html>
