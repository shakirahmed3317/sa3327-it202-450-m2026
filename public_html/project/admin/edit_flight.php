<?php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$errors = [];

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    flash("Missing flight id", "danger");
    header("Location: " . project_url("admin/list_flights.php"));
    exit;
}

if (isset($_POST["save"])) {

    $updated_values = [];

    try {

        $updated_values["airline"] = trim($_POST["airline"] ?? "");
        $updated_values["status"] = trim($_POST["status"] ?? "");
        $updated_values["departure_airport"] = trim($_POST["departure_airport"] ?? "");
        $updated_values["arrival_airport"] = trim($_POST["arrival_airport"] ?? "");
        $updated_values["departure_city"] = trim($_POST["departure_city"] ?? "");
        $updated_values["arrival_city"] = trim($_POST["arrival_city"] ?? "");

        $distance = $_POST["distance_km"] ?? "";

        if (!is_numeric($distance)) {
            throw new InvalidArgumentException("Enter a valid distance.");
        }

        $updated_values["distance_km"] = (float)$distance;

        if ($updated_values["distance_km"] < 0) {
            throw new InvalidArgumentException("Distance must be zero or greater.");
        }
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    } catch (Throwable $e) {
        error_log("Edit flight input failed: " . $e->getMessage());
        $errors[] = "Unable to process the flight values.";
    }

    if (empty($errors)) {

        $data = [
            ":id" => $id,
            ":airline" => $updated_values["airline"],
            ":status" => $updated_values["status"],
            ":departure_airport" => $updated_values["departure_airport"],
            ":arrival_airport" => $updated_values["arrival_airport"],
            ":departure_city" => $updated_values["departure_city"],
            ":arrival_city" => $updated_values["arrival_city"],
            ":distance_km" => $updated_values["distance_km"],
        ];

        try {

            update("Flights", [
                "id" => $id,
                "airline" => $updated_values["airline"],
                "status" => $updated_values["status"],
                "departure_airport" => $updated_values["departure_airport"],
                "departure_city" => $updated_values["departure_city"],
                "arrival_airport" => $updated_values["arrival_airport"],
                "arrival_city" => $updated_values["arrival_city"],
                "distance_km" => $updated_values["distance_km"],
            ]);

            flash("Flight updated", "success");
            header("Location: " . project_url("admin/list_flights.php"));
            exit;
        } catch (PDOException $e) {

            error_log("Update flight failed: " . $e->getMessage());
            $errors[] = "Unable to update flight.";
        } catch (Throwable $e) {

            error_log("Flight update helper failed: " . $e->getMessage());
            $errors[] = "Unable to update flight.";
        }
    }
}

flash_errors($errors);

try {

$flight = select(
    "SELECT
        flight_number,
        airline,
        status,
        departure_airport,
        arrival_airport,
        departure_city,
        arrival_city,
        distance_km
    FROM Flights
    WHERE id = :id
    LIMIT 1",
    [
        "id" => $id
    ]
);
} catch (PDOException $e) {

    error_log("Load flight failed: " . $e->getMessage());

    flash("Unable to load that flight.", "danger");
    header("Location: " . project_url("admin/list_flights.php"));
    exit;
}

if (!$flight) {
    flash("Flight not found", "danger");
    header("Location: " . project_url("admin/list_flights.php"));
    exit;
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Flight</title>
</head>

<body>

    <?php render_nav(); ?>

    <main>

        <h1>Edit <?php echo htmlspecialchars($flight["flight_number"]); ?></h1>

        <form method="post">

            <label for="airline">Airline</label>
            <input
                id="airline"
                name="airline"
                value="<?php echo htmlspecialchars($flight["airline"]); ?>"
                required>

            <label for="status">Status</label>
            <input
                id="status"
                name="status"
                value="<?php echo htmlspecialchars($flight["status"]); ?>"
                required>

            <label for="departure_airport">Departure Airport</label>
            <input
                id="departure_airport"
                name="departure_airport"
                value="<?php echo htmlspecialchars($flight["departure_airport"]); ?>"
                required>

            <label for="arrival_airport">Arrival Airport</label>
            <input
                id="arrival_airport"
                name="arrival_airport"
                value="<?php echo htmlspecialchars($flight["arrival_airport"]); ?>"
                required>
            <label for="departure_city">Departure City</label>
            <input
                id="departure_city"
                name="departure_city"
                value="<?php echo htmlspecialchars($flight["departure_city"]); ?>">

            <label for="arrival_city">Arrival City</label>
            <input
                id="arrival_city"
                name="arrival_city"
                value="<?php echo htmlspecialchars($flight["arrival_city"]); ?>">
            <label for="distance_km">Distance (km)</label>
            <input
                id="distance_km"
                name="distance_km"
                type="number"
                min="0"
                step="0.01"
                value="<?php echo htmlspecialchars($flight["distance_km"]); ?>"
                required>

            <button name="save" value="1" type="submit">
                Save Flight
            </button>

        </form>

    </main>

    <?php render_flash_messages(); ?>
<?php render_scripts(); ?>

</body>

</html>