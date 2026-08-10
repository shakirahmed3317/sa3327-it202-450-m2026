<?php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$user_flights = [];

//sa3327
if (isset($_POST["remove_id"])) {

    $relationship_id = (int)($_POST["remove_id"] ?? 0);

    if ($relationship_id <= 0) {

        flash("Invalid relationship.", "danger");

    } else {

        try {

            $db = getDB();

            $stmt = $db->prepare(
                "DELETE FROM UserFlights
                 WHERE id = :id"
            );

            $stmt->execute([
                ":id" => $relationship_id
            ]);

            if ($stmt->rowCount() > 0) {
                flash("Flight association removed.", "success");
            } else {
                flash("Association not found.", "warning");
            }

        } catch (Throwable $e) {

            error_log("Remove user flight association failed: " . $e->getMessage());
            flash("Unable to remove the association.", "danger");
        }
    }

    header("Location: " . project_url("admin/list_user_flights.php"));
    exit;
}


$username_search = trim($_GET["username"] ?? "");
$flight_search = trim($_GET["flight"] ?? "");


$sort = $_GET["sort"] ?? "modified";

$allowed_sort = [
    "modified" => "uf.modified DESC",
    "username" => "u.username ASC",
    "flight_number" => "f.flight_number ASC",
    "airline" => "f.airline ASC"
];

if (!isset($allowed_sort[$sort])) {
    $sort = "modified";
}



$limit = filter_input(INPUT_GET, "limit", FILTER_VALIDATE_INT);

if ($limit === false || $limit === null || $limit < 1 || $limit > 100) {
    $limit = 10;
}


$params = [];
$where = "WHERE 1=1";

if ($username_search !== "") {

    $where .= " AND u.username LIKE :username";

    $params[":username"] = "%" . $username_search . "%";
}

if ($flight_search !== "") {

    $where .= "
        AND (
            f.flight_number LIKE :flight
            OR f.airline LIKE :flight
        )
    ";

    $params[":flight"] = "%" . $flight_search . "%";
}




$matching_count = 0;

try {

    $count_row = select(
        "SELECT COUNT(*) AS total
         FROM UserFlights uf
         INNER JOIN Users u
             ON u.id = uf.user_id
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

    error_log("User flight relationship count failed: " . $e->getMessage());
    flash("Unable to count flight associations.", "danger");
}

// sa3327 8/9
try {

    $sql = "
        SELECT
            uf.id AS relationship_id,
            uf.created AS relationship_created,
            uf.modified AS relationship_modified,

            u.id AS user_id,
            u.username,

            f.id AS flight_id,
            f.flight_number,
            f.airline,
            f.departure_airport,
            f.arrival_airport,
            f.distance_km

        FROM UserFlights uf

        INNER JOIN Users u
            ON u.id = uf.user_id

        INNER JOIN Flights f
            ON f.id = uf.flight_id

        $where

        ORDER BY {$allowed_sort[$sort]}

        LIMIT $limit
    ";

    $user_flights = selectAll($sql, $params);

} catch (Throwable $e) {

    error_log("Load all user flight associations failed: " . $e->getMessage());
    flash("Unable to load flight associations.", "danger");
}

$displayed_count = count($user_flights);

?>

<!doctype html>
<html lang="en">

<head>
    <?php render_head("Flight Associations"); ?>
</head>

<body>

    <?php render_nav(); ?>

    <main class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h1>Flight Associations</h1>

        </div>


        <form method="get" class="row g-3 mb-4">

            <div class="col-md-3">

                <label class="form-label" for="username">
                    Username
                </label>

                <input
                    class="form-control"
                    id="username"
                    type="text"
                    name="username"
                    value="<?php echo htmlspecialchars($username_search); ?>"
                    placeholder="Search username">

            </div>


            <div class="col-md-3">

                <label class="form-label" for="flight">
                    Flight
                </label>

                <input
                    class="form-control"
                    id="flight"
                    type="text"
                    name="flight"
                    value="<?php echo htmlspecialchars($flight_search); ?>"
                    placeholder="Flight number or airline">

            </div>


            <div class="col-md-2">

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
                        value="username"
                        <?php if ($sort === "username") echo "selected"; ?>>
                        Username
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
            matching association<?php echo $matching_count === 1 ? "" : "s"; ?>,

            showing

            <strong><?php echo $displayed_count; ?></strong>
            <?php echo $displayed_count === 1 ? "record" : "records"; ?>.

        </div>


        <?php if (empty($user_flights)): ?>

            <div class="alert alert-info">
                No flight associations found.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead class="table-dark">

                        <tr>
                            <th>User</th>
                            <th>Flight</th>
                            <th>Airline</th>
                            <th>Route</th>
                            <th>Relationship ID</th>
                            <th>Created</th>
                            <th>Modified</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($user_flights as $row): ?>

                            <tr>

                                <td>

                                    <a
                                        href="<?php echo project_url(
                                            "profile.php?id=" .
                                            urlencode($row["user_id"])
                                        ); ?>">

                                        <?php echo htmlspecialchars(
                                            $row["username"]
                                        ); ?>

                                    </a>

                                </td>


                                <td>

                                    <a
                                        href="<?php echo project_url(
                                            "view_flight.php?id=" .
                                            urlencode($row["flight_id"])
                                        ); ?>">

                                        <?php echo htmlspecialchars(
                                            $row["flight_number"]
                                        ); ?>

                                    </a>

                                </td>


                                <td>
                                    <?php echo htmlspecialchars(
                                        $row["airline"]
                                    ); ?>
                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row["departure_airport"]
                                    );

                                    echo " → ";

                                    echo htmlspecialchars(
                                        $row["arrival_airport"]
                                    );
                                    ?>

                                </td>


                                <td>
                                    <?php echo (int)$row["relationship_id"]; ?>
                                </td>


                                <td>
                                    <?php echo htmlspecialchars(
                                        $row["relationship_created"]
                                    ); ?>
                                </td>


                                <td>
                                    <?php echo htmlspecialchars(
                                        $row["relationship_modified"]
                                    ); ?>
                                </td>


                                <td>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Remove this association?');">

                                        <input
                                            type="hidden"
                                            name="remove_id"
                                            value="<?php echo (int)$row["relationship_id"]; ?>">

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