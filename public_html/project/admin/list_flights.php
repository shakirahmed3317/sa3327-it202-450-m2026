<?php
// public_html/project/admin/list_flights.php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$flights = [];

try {

    $flights = selectAll(
        "SELECT
            id,
            flight_number,
            airline,
            aircraft_model,
            departure_airport,
            departure_city,
            arrival_airport,
            arrival_city,
            distance_km,
            is_api
        FROM Flights
        ORDER BY modified DESC
        LIMIT 10"
    );
} catch (Throwable $e) {

    error_log("List flights with helper failed: " . $e->getMessage());
    flash("Unable to load flights.", "danger");
}
?>
<!doctype html>
<html lang="en">

<head>
    <?php render_head("Flights"); ?>
</head>

<body>

    <?php render_nav(); ?>

    <main class="container py-4">

        <h1 class="mb-4">Flights</h1>

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle">

                <thead class="table-dark">
                    <tr>
                        <th>Flight</th>
                        <th>Airline</th>
                        <th>Aircraft</th>
                        <th>Departure</th>
                        <th>Arrival</th>
                        <th>Distance (km)</th>
                        <th>Source</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($flights as $flight): ?>

                        <?php
                        $source_label = $flight["is_api"] ? "API" : "Manual";
                        ?>

                        <tr>

                            <td><?php echo htmlspecialchars($flight["flight_number"]); ?></td>

                            <td><?php echo htmlspecialchars($flight["airline"]); ?></td>

                            <td><?php echo htmlspecialchars($flight["aircraft_model"]); ?></td>

                            <td>
                                <?php
                                echo htmlspecialchars($flight["departure_airport"]);
                                if (!empty($flight["departure_city"])) {
                                    echo " - " . htmlspecialchars($flight["departure_city"]);
                                }
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars($flight["arrival_airport"]);
                                if (!empty($flight["arrival_city"])) {
                                    echo " - " . htmlspecialchars($flight["arrival_city"]);
                                }
                                ?>
                            </td>

                            <td><?php echo htmlspecialchars($flight["distance_km"]); ?></td>

                            <td>
                                <?php if ($flight["is_api"]): ?>
                                    <span class="badge bg-primary">API</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Manual</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a
                                    class="btn btn-sm btn-outline-primary"
                                    href="edit_flight.php?id=<?php echo urlencode($flight["id"]); ?>">
                                    Edit
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </main>

    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>

</body>

</html>