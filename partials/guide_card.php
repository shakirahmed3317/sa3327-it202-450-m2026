<?php
// partials/guide_card.php
// List usage: render_guide_card($guide);
// Single-page usage:
// render_guide_card($guide, [
//     "show_detail_view" => true,
//     "return_to" => $return_to,
// ]);
$options = $options ?? [];
$guide = $guide ?? [];
$show_detail_view = (bool) ($options["show_detail_view"] ?? false);
$show_saved_on = (bool) ($options["show_saved_on"] ?? false);
$has_relationship_state = array_key_exists("is_saved", $guide);
$source_url = sc_nullable_url($guide["source_url"] ?? null);
$video_url = sc_nullable_url($guide["video"] ?? null);

$allowed_list_paths = [
    "guides.php",
    "my_guides.php",
    "profile.php",
    "admin/list_guides.php",
    "admin/guide_associations.php",
    "admin/unassociated_guides.php",
];
$current_url = current_project_request_url("guides.php");
$return_to = safe_project_return_url(
    $options["return_to"] ?? "",
    $allowed_list_paths,
    "guides.php"
);

// List actions return to the current filtered list. A detail page returns to
// itself after Save/Edit, but returns to its source list after Delete or Back.
$action_return_url = $current_url;
$delete_return_url = $show_detail_view ? $return_to : $current_url;
$guide_url = project_url("guide.php") . "?" . http_build_query([
    "id" => $guide["id"],
    "return_to" => $current_url,
]);
$edit_url = project_url("admin/edit_guide.php") . "?" . http_build_query([
    "id" => $guide["id"],
    "return_to" => $action_return_url,
]);
$delete_url = project_url("admin/delete_guide.php") . "?" . http_build_query([
    "id" => $guide["id"],
    "return_to" => $delete_return_url,
]);
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
                    render_csrf_input();
                    render_input([
                        "type" => "hidden",
                        "name" => "guide_id",
                        "value" => (int) $guide["id"],
                    ]);
                    render_input([
                        "type" => "hidden",
                        "name" => "return_to",
                        "value" => $action_return_url,
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
                    href="<?php echo htmlspecialchars($edit_url); ?>">Edit</a>
                <form method="post"
                    action="<?php echo htmlspecialchars($delete_url); ?>">
                    <?php
                    render_csrf_input();
                    render_button([
                        "text" => "Delete",
                        "variant" => "danger",
                    ]); ?>
                </form>
            <?php endif; ?>
            <?php if ($show_detail_view): ?>
                <a class="btn btn-secondary"
                    href="<?php echo htmlspecialchars($return_to); ?>">Back</a>
            <?php endif; ?>
        </div>
    </div>
</article>