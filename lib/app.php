<?php
$is_https = !empty($_SERVER["HTTPS"])
    && $_SERVER["HTTPS"] !== "off";

// Cookie settings must be configured before the session begins.
session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/project",
    "secure" => $is_https,
    "httponly" => true,
    "samesite" => "Lax",
]);
session_start();

require_once(__DIR__ . "/db.php");
require_once(__DIR__ . "/db_helpers.php");
require_once(__DIR__ . "/pagination_helpers.php");
require_once(__DIR__ . "/csrf_helpers.php");
require_once(__DIR__ . "/render_functions.php");
// url_helpers.php must load before helpers or partials that call project_url().
require_once(__DIR__ . "/url_helpers.php");
require_once(__DIR__ . "/validations.php");
// Keep user_helpers.php before role_helpers.php.
// has_role() depends on is_logged_in().
require_once(__DIR__ . "/user_helpers.php");
require_once(__DIR__ . "/flash_messages.php");
require_once(__DIR__ . "/duplicate_user_details.php");
// require_role() depends on flash() and project_url().
require_once(__DIR__ . "/role_helpers.php");
require_once(__DIR__ . "/api_helper.php");
require_once(__DIR__ . "/flights_api.php");
require_once(__DIR__ . "/starcraft_api.php"); 
?>