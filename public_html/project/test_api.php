<?php

require_once(__DIR__ . "/../../lib/app.php");
require_once(__DIR__ . "/../../lib/flights_api.php");

$errors = [];

$flight_number = "UA934";

$rows = search_flights($flight_number, $errors);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flight API Test</title>
</head>
<body>

<h1>Flight API Test</h1>

<?php if (!empty($errors)): ?>

    <h2>Errors</h2>

    <pre><?php print_r($errors); ?></pre>

<?php else: ?>

    <h2>Results</h2>

    <pre><?php print_r($rows); ?></pre>

<?php endif; ?>

</body>
</html>