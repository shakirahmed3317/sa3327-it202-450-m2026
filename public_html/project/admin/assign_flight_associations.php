<?php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["apply"])) {

    require_csrf_token(
        project_url("admin/assign_flight_associations.php")
    );

    $selected_users = array_map(
        "intval",
        $_POST["user_ids"] ?? []
    );

    $selected_flights = array_map(
        "intval",
        $_POST["flight_ids"] ?? []
    );

    $selected_users = array_values(
        array_filter($selected_users, fn($id) => $id > 0)
    );

    $selected_flights = array_values(
        array_filter($selected_flights, fn($id) => $id > 0)
    );

    if (empty($selected_users) || empty($selected_flights)) {

        flash(
            "Select at least one user and one flight.",
            "warning"
        );

    } else {

        try {

            $db = getDB();

            $existing = selectAll(
                "SELECT user_id, flight_id
                 FROM UserFlights"
            );

            $existing_pairs = [];

            foreach ($existing as $row) {

                $key =
                    (int)$row["user_id"] .
                    ":" .
                    (int)$row["flight_id"];

                $existing_pairs[$key] = true;
            }

            $created_count = 0;
            $removed_count = 0;

            foreach ($selected_users as $user_id) { //sa3327 08/9

                foreach ($selected_flights as $flight_id) {

                    $key =
                        $user_id .
                        ":" .
                        $flight_id;

                    if (isset($existing_pairs[$key])) {

                        $stmt = $db->prepare(
                            "DELETE FROM UserFlights
                             WHERE user_id = :user_id
                             AND flight_id = :flight_id"
                        );

                        $stmt->execute([
                            ":user_id" => $user_id,
                            ":flight_id" => $flight_id
                        ]);

                        $removed_count += $stmt->rowCount();

                    } else {

                        $stmt = $db->prepare(
                            "INSERT INTO UserFlights
                                (user_id, flight_id)
                             VALUES
                                (:user_id, :flight_id)"
                        );

                        $stmt->execute([
                            ":user_id" => $user_id,
                            ":flight_id" => $flight_id
                        ]);

                        $created_count++;
                    }
                }
            }

            flash(
                "Associations updated. Created: "
                . $created_count
                . ", Removed: "
                . $removed_count
                . ".",
                "success"
            );

        } catch (Throwable $e) {

            error_log(
                "Assign flight associations failed: "
                . $e->getMessage()
            );

            flash(
                "Unable to update flight associations.",
                "danger"
            );
        }
    }

    header(
        "Location: " .
        project_url(
            "admin/assign_flight_associations.php"
            . "?username="
            . urlencode(trim($_POST["username"] ?? ""))
            . "&flight="
            . urlencode(trim($_POST["flight"] ?? ""))
        )
    );

    exit;
}

$username_search = trim($_GET["username"] ?? "");
$flight_search = trim($_GET["flight"] ?? "");

$users = [];
$flights = [];

try {
//sa3327 08/9
    if ($username_search !== "") {

        $users = selectAll(
            "SELECT id, username
             FROM Users
             WHERE username LIKE :username
             ORDER BY username ASC
             LIMIT 25",
            [
                ":username" => "%" . $username_search . "%"
            ]
        );
    }

    if ($flight_search !== "") {

        $flights = selectAll(
            "SELECT
                id,
                flight_number,
                airline,
                departure_airport,
                arrival_airport
             FROM Flights
             WHERE
                flight_number LIKE :flight
                OR airline LIKE :flight
                OR departure_airport LIKE :flight
                OR arrival_airport LIKE :flight
             ORDER BY flight_number ASC
             LIMIT 25",
            [
                ":flight" => "%" . $flight_search . "%"
            ]
        );
    }

} catch (Throwable $e) {

    error_log(
        "Association search failed: "
        . $e->getMessage()
    );

    flash(
        "Unable to search users or flights.",
        "danger"
    );
}
?>

<!doctype html>
<html lang="en">

<head>
    <?php render_head("Assign Flight Associations"); ?>
</head>

<body>

    <?php render_nav(); ?>

    <main class="container py-4">

        <h1 class="mb-4">
            Assign Flight Associations
        </h1>

        <form method="get" class="row g-3 mb-4">

            <div class="col-md-5">

                <label
                    class="form-label"
                    for="username">
                    Search Users
                </label>

                <input
                    class="form-control"
                    id="username"
                    type="text"
                    name="username"
                    value="<?php echo htmlspecialchars($username_search); ?>"
                    placeholder="Partial username">

            </div>

            <div class="col-md-5">

                <label
                    class="form-label"
                    for="flight">
                    Search Flights
                </label>

                <input
                    class="form-control"
                    id="flight"
                    type="text"
                    name="flight"
                    value="<?php echo htmlspecialchars($flight_search); ?>"
                    placeholder="Flight number, airline, or airport">

            </div>

            <div class="col-md-2 d-flex align-items-end">

                <button
                    class="btn btn-primary w-100"
                    type="submit">
                    Search
                </button>

            </div>

        </form>

        <form method="post">

            <?php render_csrf_input(); ?>

            <input
                type="hidden"
                name="username"
                value="<?php echo htmlspecialchars($username_search); ?>">

            <input
                type="hidden"
                name="flight"
                value="<?php echo htmlspecialchars($flight_search); ?>">

            <div class="row g-4">

                <div class="col-md-6">

                    <div class="card">

                        <div class="card-header">
                            <h2 class="h5 mb-0">
                                Users
                            </h2>
                        </div>

                        <div class="card-body">

                            <?php if (empty($users)): ?>

                                <div class="alert alert-info mb-0">
                                    Search for users to display results.
                                </div>

                            <?php else: ?>

                                <?php foreach ($users as $user): ?>

                                    <div class="form-check mb-2">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="user_ids[]"
                                            value="<?php echo (int)$user["id"]; ?>"
                                            id="user_<?php echo (int)$user["id"]; ?>">

                                        <label
                                            class="form-check-label"
                                            for="user_<?php echo (int)$user["id"]; ?>">

                                            <?php echo htmlspecialchars(
                                                $user["username"]
                                            ); ?>

                                        </label>

                                    </div>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

                <div class="col-md-6">

                    <div class="card">

                        <div class="card-header">
                            <h2 class="h5 mb-0">
                                Flights
                            </h2>
                        </div>

                        <div class="card-body">

                            <?php if (empty($flights)): ?>

                                <div class="alert alert-info mb-0">
                                    Search for flights to display results.
                                </div>

                            <?php else: ?>

                                <?php foreach ($flights as $flight): ?>

                                    <div class="form-check mb-3">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="flight_ids[]"
                                            value="<?php echo (int)$flight["id"]; ?>"
                                            id="flight_<?php echo (int)$flight["id"]; ?>">

                                        <label
                                            class="form-check-label"
                                            for="flight_<?php echo (int)$flight["id"]; ?>">

                                            <strong>
                                                <?php echo htmlspecialchars(
                                                    $flight["flight_number"]
                                                ); ?>
                                            </strong>

                                            <?php if (!empty($flight["airline"])): ?>

                                                -
                                                <?php echo htmlspecialchars(
                                                    $flight["airline"]
                                                ); ?>

                                            <?php endif; ?>

                                            <br>

                                            <small class="text-muted">

                                                <?php echo htmlspecialchars(
                                                    $flight["departure_airport"]
                                                ); ?>

                                                →

                                                <?php echo htmlspecialchars(
                                                    $flight["arrival_airport"]
                                                ); ?>

                                            </small>

                                        </label>

                                    </div>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

            <div class="mt-4">

                <button
                    class="btn btn-success"
                    type="submit"
                    name="apply"
                    value="1">

                    Apply Associations

                </button>

            </div>

        </form>

    </main>

    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>

</body>

</html>