<?php
require_once(__DIR__ . "/../../lib/app.php");

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    flash("Missing flight id.", "danger");
    header("Location: " . project_url("flights.php"));
    exit;
}

try {

    $flight = select(
        "SELECT
            flight_number,
            airline,
            aircraft_model,
            status,
            departure_airport,
            departure_city,
            departure_terminal,
            departure_time,
            arrival_airport,
            arrival_city,
            arrival_terminal,
            arrival_time,
            distance_km,
            is_api
        FROM Flights
        WHERE id = :id
        LIMIT 1",
        [
            ":id" => $id
        ]
    );
} catch (Throwable $e) {

    error_log("View flight failed: " . $e->getMessage());

    flash("Unable to load flight.", "danger");
    header("Location: " . project_url("flights.php"));
    exit;
}

if (!$flight) {
    flash("Flight not found.", "danger");
    header("Location: " . project_url("flights.php"));
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

        <h1 class="mb-4">
            Flight <?php echo htmlspecialchars($flight["flight_number"]); ?>
        </h1>

        <div class="card">

            <div class="card-body">

                <table class="table table-bordered">

                    <tr>
                        <th>Flight Number</th>
                        <td><?php echo htmlspecialchars($flight["flight_number"]); ?></td>
                    </tr>

                    <tr>
                        <th>Airline</th>
                        <td><?php echo htmlspecialchars($flight["airline"]); ?></td>
                    </tr>

                    <tr>
                        <th>Aircraft</th>
                        <td><?php echo htmlspecialchars($flight["aircraft_model"]); ?></td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td><?php echo htmlspecialchars($flight["status"]); ?></td>
                    </tr>

                    <tr>
                        <th>Departure</th>
                        <td>
                            <?php
                            echo htmlspecialchars($flight["departure_airport"]);

                            if (!empty($flight["departure_city"])) {
                                echo " - " . htmlspecialchars($flight["departure_city"]);
                            }

                            if (!empty($flight["departure_terminal"])) {
                                echo " (Terminal " . htmlspecialchars($flight["departure_terminal"]) . ")";
                            }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Departure Time</th>
                        <td><?php echo htmlspecialchars($flight["departure_time"]); ?></td>
                    </tr>

                    <tr>
                        <th>Arrival</th>
                        <td>
                            <?php
                            echo htmlspecialchars($flight["arrival_airport"]);

                            if (!empty($flight["arrival_city"])) {
                                echo " - " . htmlspecialchars($flight["arrival_city"]);
                            }

                            if (!empty($flight["arrival_terminal"])) {
                                echo " (Terminal " . htmlspecialchars($flight["arrival_terminal"]) . ")";
                            }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Arrival Time</th>
                        <td><?php echo htmlspecialchars($flight["arrival_time"]); ?></td>
                    </tr>

                    <tr>
                        <th>Distance</th>
                        <td><?php echo htmlspecialchars($flight["distance_km"]); ?> km</td>
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

                </table>

                <div class="d-flex gap-2">

                    <a
                        class="btn btn-secondary"
                        href="<?php echo project_url("flights.php"); ?>">
                        Back to Flights
                    </a>

                    <?php if (has_role("Admin")): ?>

                        <a
                            class="btn btn-primary"
                            href="<?php echo project_url("admin/edit_flight.php?id=" . urlencode($id)); ?>">
                            Edit
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </main>

    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>

</body>

</html>