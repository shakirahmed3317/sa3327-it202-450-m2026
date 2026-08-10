<?php
$roles = ["admin", "student", "ta"];

foreach ($roles as $role) {
    echo "$role<br>";
}

$profile = [
    "ucid" => "mt85",
    "role" => "developer",
    "active" => true
];

foreach ($profile as $key => $value) {
    echo "$key: ";
    var_export($value);
    echo "<br>";
}
