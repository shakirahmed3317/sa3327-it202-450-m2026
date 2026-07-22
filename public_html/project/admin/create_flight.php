<?php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$errors = [];
$row = null;
$active_form = "fetch";

if (isset($_POST["fetch_flight"])) {
    $active_form = "fetch";

    $flight_number = "";
    if (isset($_POST["flight_number"])) {
        $submitted = $_POST["flight_number"];
        if (is_string($submitted)) {
            $flight_number = trim($submitted);
        }
    }

    if ($flight_number === "") {
        $errors[] = "Enter a flight number before fetching.";
    }

    if (empty($errors)) {
        try {
            $flights = search_flights($flight_number, $errors);

            if (!empty($flights)) {
                $row = $flights[0];
                $row["is_api"] = 1;
            }
        } catch (Throwable $e) {
            error_log("Fetch flight failed: " . $e->getMessage());
            $errors[] = "Unable to fetch that flight right now.";
        }

        if (empty($errors) && !$row) {
            $errors[] = "No matching flight was found.";
        }
    }
} elseif (isset($_POST["create_flight"])) {

    $active_form = "create";

    try {

        $flight_number = trim($_POST["flight_number"] ?? "");

        if ($flight_number === "") {
            throw new InvalidArgumentException("Enter a flight number.");
        }

        $row = [
            "flight_number" => $flight_number,
            "callsign" => trim($_POST["callsign"] ?? ""),
            "departure_airport" => trim($_POST["departure_airport"] ?? ""),
            "arrival_airport" => trim($_POST["arrival_airport"] ?? ""),
            "last_flight" => $_POST["last_flight"] ?? null,
            "count_30d" => (int)($_POST["count_30d"] ?? 0),
            "delay_30d" => (float)($_POST["delay_30d"] ?? 0),
            "cancelled_30d" => (int)($_POST["cancelled_30d"] ?? 0),
            "avg_delay" => (float)($_POST["avg_delay"] ?? 0),
            "distance_km" => (float)($_POST["distance_km"] ?? 0),
            "avg_duration" => (float)($_POST["avg_duration"] ?? 0),
            "aircraft_models" => trim($_POST["aircraft_models"] ?? ""),
            "is_api" => 0,
        ];
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }
}

if ($row && empty($errors)) {

    $insert_row = [
        ":flight_number" => $row["flight_number"],
        ":call_sign" => $row["call_sign"],
        ":airline" => $row["airline"],
        ":aircraft_model" => $row["aircraft_model"],
        ":status" => $row["status"],
        ":departure_airport" => $row["departure_airport"],
        ":departure_city" => $row["departure_city"],
        ":departure_terminal" => $row["departure_terminal"],
        ":departure_time" => $row["departure_time"],
        ":arrival_airport" => $row["arrival_airport"],
        ":arrival_city" => $row["arrival_city"],
        ":arrival_terminal" => $row["arrival_terminal"],
        ":arrival_time" => $row["arrival_time"],
        ":distance_km" => $row["distance_km"],
        ":is_api" => $row["is_api"],
    ];

    try {

        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO Flights (
        flight_number,
        call_sign,
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
    ) VALUES (
        :flight_number,
        :call_sign,
        :airline,
        :aircraft_model,
        :status,
        :departure_airport,
        :departure_city,
        :departure_terminal,
        :departure_time,
        :arrival_airport,
        :arrival_city,
        :arrival_terminal,
        :arrival_time,
        :distance_km,
        :is_api
    )"
        );

        $stmt->execute($insert_row);

        flash("Created flight " . $row["flight_number"], "success");
        header("Location: " . project_url("admin/list_flights.php"));
        exit;
    } catch (PDOException $e) {
        die($e->getMessage());
        // error_log($e->getMessage());
        // flash("Unable to create flight.", "danger");
    }
}

flash_errors($errors);

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Flight</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>

        <h1>Create Flight</h1>

        <div aria-label="Flight creation mode" role="group">
            <button data-form-mode-button="fetch" type="button">
                Fetch From API
            </button>

            <button data-form-mode-button="create" type="button">
                Create Manually
            </button>
        </div>

        <section data-form-mode-panel="fetch" <?php if ($active_form !== "fetch") {
                                                    echo " hidden";
                                                } ?>>

            <form method="post">

                <h2>Fetch From API</h2>

                <label for="flight_fetch">
                    Flight Number
                </label>

                <input
                    id="flight_fetch"
                    name="flight_number"
                    required>

                <button
                    name="fetch_flight"
                    value="1"
                    type="submit">
                    Fetch Flight
                </button>

            </form>

        </section>

        <section data-form-mode-panel="create" <?php if ($active_form !== "create") {
                                                    echo " hidden";
                                                } ?>>

            <form method="post">

                <h2>Create Manually</h2>

                <label for="flight_number">Flight Number</label>
                <input id="flight_number" name="flight_number" required>

                <label for="callsign">Callsign</label>
                <input id="callsign" name="callsign">

                <label for="departure_airport">Departure Airport</label>
                <input id="departure_airport" name="departure_airport">

                <label for="arrival_airport">Arrival Airport</label>
                <input id="arrival_airport" name="arrival_airport">

                <label for="last_flight">Last Flight</label>
                <input id="last_flight" name="last_flight" type="date">

                <label for="count_30d">Flights (30 Days)</label>
                <input id="count_30d" name="count_30d" type="number">

                <label for="delay_30d">Delay Rate</label>
                <input id="delay_30d" name="delay_30d" type="number" step="0.01">

                <label for="cancelled_30d">Cancelled</label>
                <input id="cancelled_30d" name="cancelled_30d" type="number">

                <label for="avg_delay">Average Delay</label>
                <input id="avg_delay" name="avg_delay" type="number" step="0.01">

                <label for="distance_km">Distance (km)</label>
                <input id="distance_km" name="distance_km" type="number" step="0.01">

                <label for="avg_duration">Average Duration</label>
                <input id="avg_duration" name="avg_duration" type="number" step="0.01">

                <label for="aircraft_models">Aircraft Models</label>
                <input id="aircraft_models" name="aircraft_models">

                <button
                    name="create_flight"
                    value="1"
                    type="submit">
                    Create Flight
                </button>

            </form>

        </section>

    </main>

    <?php render_flash_messages(); ?>

    <script>
        const flightFormButtons = document.querySelectorAll("[data-form-mode-button]");
        const flightFormPanels = document.querySelectorAll("[data-form-mode-panel]");

        function showFlightForm(mode) {

            flightFormPanels.forEach(function(panel) {
                panel.hidden = panel.dataset.formModePanel !== mode;
            });

            flightFormButtons.forEach(function(button) {
                button.setAttribute(
                    "aria-pressed",
                    button.dataset.formModeButton === mode ? "true" : "false"
                );
            });

        }

        flightFormButtons.forEach(function(button) {

            button.addEventListener("click", function() {
                showFlightForm(button.dataset.formModeButton);
            });

        });

        showFlightForm("<?php echo $active_form; ?>");
    </script>

</body>

</html>