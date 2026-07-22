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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flights</title>
</head>

<body>

    <?php render_nav(); ?>

    <main>

        <h1>Flights</h1>

        <table>

            <thead>
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
                    $source_label = "Manual";

                    if ($flight["is_api"]) {
                        $source_label = "API";
                    }
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

                        <td><?php echo $source_label; ?></td>

                        <td>
                            <a href="edit_flight.php?id=<?php echo urlencode($flight["id"]); ?>">
                                Edit
                            </a>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </main>

    <?php render_flash_messages(); ?>

</body>
<style>
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 10px 14px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
</style>

</html>