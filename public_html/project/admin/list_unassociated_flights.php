<?php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$flights = [];


$search = trim($_GET["q"] ?? "");



$sort = $_GET["sort"] ?? "modified";

$allowed_sort = [
    "modified" => "f.modified DESC",
    "flight_number" => "f.flight_number ASC",
    "airline" => "f.airline ASC",
    "distance" => "f.distance_km DESC"
];

if (!isset($allowed_sort[$sort])) {
    $sort = "modified";
}

$limit = filter_input(INPUT_GET, "limit", FILTER_VALIDATE_INT);

if ($limit === false || $limit === null || $limit < 1 || $limit > 100) {
    $limit = 10;
}

$params = [];

$where = "
    WHERE NOT EXISTS (
        SELECT 1
        FROM UserFlights uf
        WHERE uf.flight_id = f.id
    )
";

if ($search !== "") {

    $where .= "
        AND (
            f.flight_number LIKE :search
            OR f.airline LIKE :search
            OR f.departure_airport LIKE :search
            OR f.arrival_airport LIKE :search
        )
    ";

    $params[":search"] = "%" . $search . "%";
}

$matching_count = 0;

try {

    $count_row = select(
        "SELECT COUNT(*) AS total
         FROM Flights f
         $where
         LIMIT 1",
        $params
    );

    if ($count_row) {
        $matching_count = (int)$count_row["total"];
    }

} catch (Throwable $e) {

    error_log("Unassociated flight count failed: " . $e->getMessage());
    flash("Unable to count unassociated flights.", "danger");
}

try {

    $sql = "
        SELECT
            f.id,
            f.flight_number,
            f.airline,
            f.aircraft_model,
            f.status,
            f.departure_airport,
            f.departure_city,
            f.arrival_airport,
            f.arrival_city,
            f.distance_km,
            f.is_api

        FROM Flights f

        $where

        ORDER BY {$allowed_sort[$sort]}

        LIMIT $limit
    ";

    $flights = selectAll($sql, $params);

} catch (Throwable $e) {

    error_log("Load unassociated flights failed: " . $e->getMessage());
    flash("Unable to load unassociated flights.", "danger");
}

$displayed_count = count($flights);

?>

<!doctype html>
<html lang="en">

<head>
    <?php render_head("Unassociated Flights"); ?>
</head>

<body>

    <?php render_nav(); ?>

    <main class="container py-4">

        <h1 class="mb-4">Unassociated Flights</h1>

        <form method="get" class="row g-3 mb-4">

            <div class="col-md-5">

                <label class="form-label" for="q">
                    Search
                </label>

                <input
                    class="form-control"
                    id="q"
                    type="text"
                    name="q"
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Flight number, airline, or airport">

            </div>


            <div class="col-md-3">

                <label class="form-label" for="sort">
                    Sort By
                </label>

                <select
                    class="form-select"
                    id="sort"
                    name="sort">

                    <option
                        value="modified"
                        <?php if ($sort === "modified") echo "selected"; ?>>
                        Newest
                    </option>

                    <option
                        value="flight_number"
                        <?php if ($sort === "flight_number") echo "selected"; ?>>
                        Flight Number
                    </option>

                    <option
                        value="airline"
                        <?php if ($sort === "airline") echo "selected"; ?>>
                        Airline
                    </option>

                    <option
                        value="distance"
                        <?php if ($sort === "distance") echo "selected"; ?>>
                        Distance
                    </option>

                </select>

            </div>


            <div class="col-md-2">

                <label class="form-label" for="limit">
                    Limit
                </label>

                <input
                    class="form-control"
                    id="limit"
                    type="number"
                    name="limit"
                    min="1"
                    max="100"
                    value="<?php echo $limit; ?>">

            </div>


            <div class="col-md-2 d-flex align-items-end">

                <button
                    class="btn btn-primary w-100"
                    type="submit">
                    Search
                </button>

            </div>

        </form>


        <div class="mb-3">

            <strong><?php echo $matching_count; ?></strong>
            matching unassociated
            <?php echo $matching_count === 1 ? "flight" : "flights"; ?>,

            showing

            <strong><?php echo $displayed_count; ?></strong>
            <?php echo $displayed_count === 1 ? "record" : "records"; ?>.

        </div>


        <?php if (empty($flights)): ?>

            <div class="alert alert-info">
                No unassociated flights found.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead class="table-dark">

                        <tr>
                            <th>Flight</th>
                            <th>Airline</th>
                            <th>Aircraft</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Distance</th>
                            <th>Source</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($flights as $flight): ?>

                            <tr>

                                <td>

                                    <a
                                        href="<?php echo project_url(
                                            "view_flight.php?id=" .
                                            urlencode($flight["id"])
                                        ); ?>">

                                        <?php echo htmlspecialchars(
                                            $flight["flight_number"]
                                        ); ?>

                                    </a>

                                </td>


                                <td>
                                    <?php echo htmlspecialchars(
                                        $flight["airline"]
                                    ); ?>
                                </td>


                                <td>
                                    <?php echo htmlspecialchars(
                                        $flight["aircraft_model"]
                                    ); ?>
                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $flight["departure_airport"]
                                    );

                                    if (!empty($flight["departure_city"])) {
                                        echo " - " .
                                            htmlspecialchars(
                                                $flight["departure_city"]
                                            );
                                    }
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $flight["arrival_airport"]
                                    );

                                    if (!empty($flight["arrival_city"])) {
                                        echo " - " .
                                            htmlspecialchars(
                                                $flight["arrival_city"]
                                            );
                                    }
                                    ?>

                                </td>


                                <td>
                                    <?php echo htmlspecialchars(
                                        $flight["distance_km"]
                                    ); ?>
                                    km
                                </td>


                                <td>

                                    <?php if ($flight["is_api"]): ?>

                                        <span class="badge bg-primary">
                                            API
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            Manual
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </main>

    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>

</body>

</html>