<?php
require_once(__DIR__ . "/../../../lib/app.php");

if (!is_logged_in()) {
    flash("You must be logged in to save flights.", "warning");
    header("Location: " . project_url("login.php"));
    exit;
}

$user_id = get_user_id();
$flight_id = (int)($_POST["flight_id"] ?? 0);
$action = $_POST["action"] ?? "";

if ($flight_id <= 0) {
    flash("Invalid flight.", "danger");
    header("Location: " . project_url("flights.php"));
    exit;
}

if ($action !== "save" && $action !== "remove") {
    flash("Invalid flight action.", "danger");
    header("Location: " . project_url("flights.php"));
    exit;
}

try {

    if ($action === "save") {

        try {
            insert("UserFlights", [
                "user_id" => $user_id,
                "flight_id" => $flight_id
            ]);

            flash("Flight saved.", "success");

        } catch (PDOException $e) {

            $error_code = 0;

            if (isset($e->errorInfo[1])) {
                $error_code = (int)$e->errorInfo[1];
            }

            if ($error_code === 1062) {
                flash("You have already saved this flight.", "warning");
            } else {
                throw $e;
            }
        }

    } else {

        $db = getDB();

        $stmt = $db->prepare(
            "DELETE FROM UserFlights
             WHERE user_id = :user_id
             AND flight_id = :flight_id"
        );

        $stmt->execute([
            ":user_id" => $user_id,
            ":flight_id" => $flight_id
        ]);

        flash("Flight removed from your saved flights.", "success");
    }

} catch (Throwable $e) {

    error_log("Toggle saved flight failed: " . $e->getMessage());
    flash("Unable to update your saved flights.", "danger");
}

header("Location: " . project_url("view_flight.php?id=" . $flight_id));
exit;