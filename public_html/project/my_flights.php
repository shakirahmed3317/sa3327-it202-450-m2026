<?php
require_once(__DIR__ . "/../../lib/app.php");

if (!is_logged_in()) {
    flash("You must be logged in to view your saved flights.", "warning");
    header("Location: " . project_url("login.php"));
    exit;
}

$user_id = get_user_id();
$errors = [];
//sa3327 8/9
if (isset($_POST["remove_id"])) {

    $relationship_id = (int)($_POST["remove_id"] ?? 0);

    if ($relationship_id <= 0) {

        flash("Invalid saved flight.", "danger");

    } else {

        try {

            $db = getDB();

            $stmt = $db->prepare(
                "DELETE FROM UserFlights
                 WHERE id = :id
                 AND user_id = :user_id"
            );

            $stmt->execute([
                ":id" => $relationship_id,
                ":user_id" => $user_id
            ]);

            if ($stmt->rowCount() > 0) {
                flash("Flight removed from your saved flights.", "success");
            } else {
                flash("Saved flight was not found.", "warning");
            }
        } catch (Throwable $e) {
            error_log("Remove saved flight failed: " . $e->getMessage());
            flash("Unable to remove the saved flight.", "danger");
        }
    }

    header("Location: " . project_url("my_flights.php"));
    exit;
}

if (isset($_POST["remove_all"])) {

    try {

        $db = getDB();

        $stmt = $db->prepare(
            "DELETE FROM UserFlights
             WHERE user_id = :user_id"
        );

        $stmt->execute([
            ":user_id" => $user_id
        ]);

        flash("All saved flights were removed.", "success");

    } catch (Throwable $e) {

        error_log("Remove all saved flights failed: " . $e->getMessage());
        flash("Unable to remove your saved flights.", "danger");
    }

    header("Location: " . project_url("my_flights.php"));
    exit;
}

$search = trim($_GET["q"] ?? "");

// sa3327 8/9
$sort = $_GET["sort"] ?? "modified";

$allowed_sort = [
    "modified" => "uf.modified DESC",
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

$params = [
    ":user_id" => $user_id
];

$where = "WHERE uf.user_id = :user_id";

if ($search !== "") {

    $where .= "
        AND (
            f.flight_number LIKE :search
            OR f.airline LIKE :search
        )
    ";

    $params[":search"] = "%{$search}%";
}

$matching_count = 0;

try {

    $count_row = select(
        "SELECT COUNT(*) AS total
         FROM UserFlights uf
         INNER JOIN Flights f
             ON f.id = uf.flight_id
         $where
         LIMIT 1",
        $params
    );

    if ($count_row) {
        $matching_count = (int)$count_row["total"];
    }

} catch (Throwable $e) {

    error_log("Saved flight count failed: " . $e->getMessage());
    flash("Unable to count saved flights.", "danger");
}


$flights = [];
//sa3327 8/9
try {

    $sql = "
        SELECT
            uf.id AS relationship_id,
            uf.created AS relationship_created,
            uf.modified AS relationship_modified,

            f.id AS flight_id,
            f.flight_number,
            f.airline,
            f.aircraft_model,
            f.departure_airport,
            f.departure_city,
            f.arrival_airport,
            f.arrival_city,
            f.distance_km

        FROM UserFlights uf

        INNER JOIN Flights f
            ON f.id = uf.flight_id

        $where

        ORDER BY {$allowed_sort[$sort]}

        LIMIT $limit
    ";

    $flights = selectAll($sql, $params);

} catch (Throwable $e) {

    error_log("Load saved flights failed: " . $e->getMessage());
    flash("Unable to load your saved flights.", "danger");
}

$displayed_count = count($flights);

?>

<!doctype html>
<html lang="en">

<head>
    <?php render_head("My Saved Flights"); ?>
</head>

<body>

    <?php render_nav(); ?>

    <main class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h1>My Saved Flights</h1>

            <?php if ($matching_count > 0): ?>

                <form
                    method="post"
                    onsubmit="return confirm('Remove all of your saved flights?');">

                    <button
                        class="btn btn-outline-danger"
                        type="submit"
                        name="remove_all"
                        value="1">
                        Remove All
                    </button>

                </form>

            <?php endif; ?>

        </div>


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
                    placeholder="Flight number or airline">

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

            <strong>
                <?php echo $matching_count; ?>
            </strong>

            matching saved flight<?php echo $matching_count === 1 ? "" : "s"; ?>,

            showing

            <strong>
                <?php echo $displayed_count; ?>
            </strong>

            <?php echo $displayed_count === 1 ? "record" : "records"; ?>.

        </div>


        <?php if (empty($flights)): ?>

            <div class="alert alert-info">
                No saved flights found.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead class="table-dark">

                        <tr>
                            <th>Flight</th>
                            <th>Airline</th>
                            <th>Route</th>
                            <th>Distance</th>
                            <th>Saved</th>
                            <th>Modified</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($flights as $flight): ?>

                            <tr>

                                <td>

                                    <a
                                        href="<?php echo project_url(
                                            "view_flight.php?id=" .
                                            urlencode($flight["flight_id"])
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

                                    <?php
                                    echo htmlspecialchars(
                                        $flight["departure_airport"]
                                    );

                                    if (!empty($flight["departure_city"])) {
                                        echo " - " . htmlspecialchars(
                                            $flight["departure_city"]
                                        );
                                    }

                                    echo " -> ";

                                    echo htmlspecialchars(
                                        $flight["arrival_airport"]
                                    );

                                    if (!empty($flight["arrival_city"])) {
                                        echo " - " . htmlspecialchars(
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
                                    <?php echo htmlspecialchars(
                                        $flight["relationship_created"]
                                    ); ?>
                                </td>


                                <td>
                                    <?php echo htmlspecialchars(
                                        $flight["relationship_modified"]
                                    ); ?>
                                </td>


                                <td>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Remove this saved flight?');">

                                        <input
                                            type="hidden"
                                            name="remove_id"
                                            value="<?php echo htmlspecialchars(
                                                $flight["relationship_id"]
                                            ); ?>">

                                        <button
                                            class="btn btn-sm btn-outline-danger"
                                            type="submit">

                                            Remove

                                        </button>

                                    </form>

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