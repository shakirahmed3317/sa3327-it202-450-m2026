<?php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    flash("Invalid flight.", "danger");
    header("Location: " . project_url("admin/list_flights.php"));
    exit;
}

try {

    $flight = select(
        "SELECT *
    FROM Flights
    WHERE id = :id
    LIMIT 1",
        [
            ":id" => $id
        ]
    );

    if (!$flight) {
        flash("Flight not found.", "warning");
        header("Location: " . project_url("admin/list_flights.php"));
        exit;
    }
} catch (Throwable $e) {

    error_log("View flight failed: " . $e->getMessage());
    flash("Unable to load flight.", "danger");
    header("Location: " . project_url("admin/list_flights.php"));
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Flight Details"); ?>
</head>

<body>

    <?php render_nav(); ?>

    <main class="container py-4">

        <h1 class="mb-4">Flight Details</h1>

        <div class="card">

            <div class="card-body">

                <table class="table table-bordered">

                    <tr>
                        <th>Flight Number</th>
                        <td><?= htmlspecialchars($flight["flight_number"]) ?></td>
                    </tr>

                    <tr>
                        <th>Airline</th>
                        <td><?= htmlspecialchars($flight["airline"]) ?></td>
                    </tr>

                    <tr>
                        <th>Aircraft</th>
                        <td><?= htmlspecialchars($flight["aircraft_model"]) ?></td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td><?= htmlspecialchars($flight["status"]) ?></td>
                    </tr>

                    <tr>
                        <th>Departure</th>
                        <td>
                            <?= htmlspecialchars($flight["departure_airport"]) ?>
                            <?php if (!empty($flight["departure_city"])): ?>
                                - <?= htmlspecialchars($flight["departure_city"]) ?>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Arrival</th>
                        <td>
                            <?= htmlspecialchars($flight["arrival_airport"]) ?>
                            <?php if (!empty($flight["arrival_city"])): ?>
                                - <?= htmlspecialchars($flight["arrival_city"]) ?>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Distance</th>
                        <td><?= htmlspecialchars($flight["distance_km"]) ?> km</td>
                    </tr>

                    <tr>
                        <th>Source</th>
                        <td>
                            <?php if ($flight["is_api"]): ?>
                                <span class="badge bg-primary">API</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Manual</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Created</th>
                        <td><?= htmlspecialchars($flight["created"]) ?></td>
                    </tr>

                    <tr>
                        <th>Modified</th>
                        <td><?= htmlspecialchars($flight["modified"]) ?></td>
                    </tr>

                </table>

                <a
                    class="btn btn-secondary"
                    href="<?= project_url("admin/list_flights.php") ?>">
                    Back
                </a>

                <a
                    class="btn btn-primary"
                    href="edit_flight.php?id=<?= urlencode($flight["id"]) ?>">
                    Edit
                </a>

            </div>

        </div>

    </main>

    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>

</body>

</html>