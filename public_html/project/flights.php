<?php
require_once(__DIR__ . "/../../lib/app.php");

$flights = [];

$search = trim($_GET["q"] ?? "");

$sort = $_GET["sort"] ?? "modified";

$allowed_sort = [
    "modified" => "modified DESC",
    "flight_number" => "flight_number ASC",
    "airline" => "airline ASC",
    "distance" => "distance_km DESC"
];

if (!isset($allowed_sort[$sort])) {
    $sort = "modified";
}

$limit = filter_input(INPUT_GET, "limit", FILTER_VALIDATE_INT);

if ($limit === false || $limit === null || $limit < 1 || $limit > 100) {
    $limit = 10;
}

$params = [];
$where = "";

if ($search !== "") {
    $where = "WHERE flight_number LIKE :search OR airline LIKE :search";
    $params[":search"] = "%{$search}%";
}

$sql = "
SELECT
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
$where
ORDER BY {$allowed_sort[$sort]}
LIMIT $limit
";

try {

    $flights = selectAll($sql, $params);

} catch (Throwable $e) {

    error_log($e->getMessage());
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

    <form method="get" class="row g-3 mb-4">

        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input
                class="form-control"
                type="text"
                name="q"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label">Sort By</label>

            <select class="form-select" name="sort">
                <option value="modified" <?= $sort == "modified" ? "selected" : "" ?>>Newest</option>
                <option value="flight_number" <?= $sort == "flight_number" ? "selected" : "" ?>>Flight Number</option>
                <option value="airline" <?= $sort == "airline" ? "selected" : "" ?>>Airline</option>
                <option value="distance" <?= $sort == "distance" ? "selected" : "" ?>>Distance</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Limit</label>

            <input
                class="form-control"
                type="number"
                min="1"
                max="100"
                name="limit"
                value="<?php echo $limit; ?>">
        </div>

        <div class="col-md-3 d-flex align-items-end">

            <button class="btn btn-primary w-100">
                Search
            </button>

        </div>

    </form>

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
                </tr>
            </thead>

            <tbody>

            <?php if (empty($flights)): ?>

                <tr>
                    <td colspan="7" class="text-center">
                        No flights found.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($flights as $flight): ?>

                    <tr>

                        <td>
                            <a href="<?php echo project_url("view_flight.php?id=" . urlencode($flight["id"])); ?>">
                                <?php echo htmlspecialchars($flight["flight_number"]); ?>
                            </a>
                        </td>

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

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

<?php render_flash_messages(); ?>
<?php render_scripts(); ?>

</body>

</html>