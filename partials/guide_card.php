<?php
// partials/guide_card.php
// List usage: render_guide_card($guide);
// Single-page usage: render_guide_card($guide, ["show_detail_view" => true]);
$options = $options ?? [];
$guide = $guide ?? [];
$show_detail_view = (bool) ($options["show_detail_view"] ?? false);
$show_saved_on = (bool) ($options["show_saved_on"] ?? false);
$has_relationship_state = array_key_exists("is_saved", $guide);
$source_url = sc_nullable_url($guide["source_url"] ?? null);
$video_url = sc_nullable_url($guide["video"] ?? null);

$guide_url = project_url("guide.php") . "?" . http_build_query([
    "id" => $guide["id"],
]);
$return_to = $_SERVER["REQUEST_URI"] ?? $guide_url;
if (!is_string($return_to) || $return_to === "") {
    $return_to = $guide_url;
}
?>
<article class="card h-100">
    <div class="card-body d-flex flex-column">
        <?php if ($show_detail_view): ?>
            <h1 class="card-title">
                <?php echo htmlspecialchars((string) $guide["title"]); ?>
            </h1>
        <?php else: ?>
            <h2 class="card-title h5">
                <?php echo htmlspecialchars((string) $guide["title"]); ?>
            </h2>
        <?php endif; ?>
        <p class="text-body-secondary">
            <?php echo htmlspecialchars((string) $guide["game"]); ?> |
            <?php echo htmlspecialchars((string) ($guide["primary_category"] ?? "Uncategorized")); ?> |
            <?php echo htmlspecialchars((string) ($guide["status"] ?? "Unknown status")); ?>
        </p>

        <dl class="row small">
            <dt class="col-5">Player Race</dt>
            <dd class="col-7">
                <?php echo htmlspecialchars((string) ($guide["player_race"] ?? "Any")); ?>
            </dd>
            <dt class="col-5">Opponent Race</dt>
            <dd class="col-7">
                <?php echo htmlspecialchars((string) ($guide["opponent_race"] ?? "Any")); ?>
            </dd>
            <?php if (!empty($guide["matchup"])): ?>
                <dt class="col-5">Matchup</dt>
                <dd class="col-7">
                    <?php echo htmlspecialchars((string) $guide["matchup"]); ?>
                </dd>
            <?php endif; ?>
        </dl>

        <?php if (!empty($guide["excerpt"])): ?>
            <p class="lead fs-6">
                <?php echo htmlspecialchars((string) $guide["excerpt"]); ?>
            </p>
        <?php endif; ?>
        <?php if (!empty($guide["summary"])): ?>
            <p class="card-text">
                <?php echo nl2br(htmlspecialchars((string) $guide["summary"])); ?>
            </p>
        <?php endif; ?>
        <?php if ($source_url !== null): ?>
            <p>
                <a href="<?php echo htmlspecialchars($source_url); ?>"
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
        <?php if ($show_saved_on && !empty($guide["saved_on"])): ?>
            <p class="small text-body-secondary">
                Saved <?php echo htmlspecialchars((string) $guide["saved_on"]); ?>
            </p>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2 mt-auto">
            <?php if (!$show_detail_view): ?>
                <a class="btn btn-primary"
                    href="<?php echo htmlspecialchars($guide_url); ?>">View</a>
            <?php endif; ?>
            <?php if (is_logged_in() && $has_relationship_state): ?>
                <form method="post"
                    action="<?php echo project_url("internal/toggle_saved_guide.php"); ?>">
                    <?php
                    render_input([
                        "type" => "hidden",
                        "name" => "guide_id",
                        "value" => (int) $guide["id"],
                    ]);
                    render_input([
                        "type" => "hidden",
                        "name" => "return_to",
                        "value" => $return_to,
                    ]);

                    $is_saved = (int) ($guide["is_saved"] ?? 0);
                    $new_is_saved = $is_saved === 1 ? 0 : 1;
                    render_input([
                        "type" => "hidden",
                        "name" => "new_is_saved",
                        "value" => $new_is_saved,
                    ]);

                    $button_text = "Save Guide";
                    if ($is_saved === 1) {
                        $button_text = "Remove Saved Guide";
                    }
                    render_button([
                        "text" => $button_text,
                        "variant" => "primary",
                    ]);
                    ?>
                </form>
            <?php endif; ?>
            <?php if (has_role("Admin")): ?>
                <a class="btn btn-warning"
                    href="<?php echo project_url("admin/edit_guide.php?id=" . (int) $guide["id"]); ?>">
                    Edit
                </a>
                <form method="post"
                    action="<?php echo project_url(
                        "admin/delete_guide.php?id=" . (int) $guide["id"]
                            . "&return_to=guides.php"
                    ); ?>">
                    <?php render_button([
                        "text" => "Delete",
                        "variant" => "danger",
                    ]); ?>
                </form>
            <?php endif; ?>
            <?php if ($show_detail_view): ?>
                <a class="btn btn-secondary"
                    href="<?php echo project_url("guides.php"); ?>">Back To Guides</a>
            <?php endif; ?>
        </div>
    </div>
</article>