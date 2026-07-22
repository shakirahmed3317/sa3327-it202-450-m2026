<?php
// public_html/project/admin/list_flights.php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$flights = [];

try {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT
            id,
            flight_number,
            airline,
            aircraft_model,
            departure_airport,
            arrival_airport,
            distance_km,
            is_api
        FROM Flights
        ORDER BY modified DESC
        LIMIT 10"
    );

    $stmt->execute();
    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("List flights failed: " . $e->getMessage());
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

                        <td><?php echo htmlspecialchars($flight["departure_airport"]); ?></td>

                        <td><?php echo htmlspecialchars($flight["arrival_airport"]); ?></td>

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

</html>