<?php
// ============================================================
// moderator.php  —  Moderator Routes
// Base path: /api/moderator
// ============================================================

require_once __DIR__ . "/../controllers/moderatorController.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$segments = explode("/", trim($uri, "/"));


$resource = $segments[2] ?? "";   // dashboard | listings | reports | users
$param = $segments[3] ?? "";   // numeric id | "pending" | ""
$subAction = $segments[4] ?? "";   // approve | reject | edit | warn | suspend | reinstate | ""

match (true) {

    // GET /api/moderator/dashboard
    $method === "GET" && $resource === "dashboard"
    => getDashboard($conn),

    // listing moderation

    // GET /api/moderator/listings/pending
    $method === "GET" && $resource === "listings" && $param === "pending"
    => getPendingListings($conn),

    // GET /api/moderator/listings  (all, filterable)
    $method === "GET" && $resource === "listings" && $param === ""
    => getAllListingsMod($conn),

    // PUT /api/moderator/listings/{id}/approve
    $method === "PUT" && $resource === "listings" && is_numeric($param) && $subAction === "approve"
    => approveListing($conn, (int) $param),

    // PUT /api/moderator/listings/{id}/reject
    $method === "PUT" && $resource === "listings" && is_numeric($param) && $subAction === "reject"
    => rejectListing($conn, (int) $param),

    // PUT /api/moderator/listings/{id}/edit
    $method === "PUT" && $resource === "listings" && is_numeric($param) && $subAction === "edit"
    => moderatorEditListing($conn, (int) $param),

    // DELETE /api/moderator/listings/{id}
    $method === "DELETE" && $resource === "listings" && is_numeric($param) && $subAction === ""
    => moderatorRemoveListing($conn, (int) $param),

    // reports

    // GET /api/moderator/reports
    $method === "GET" && $resource === "reports" && $param === ""
    => getReports($conn),

    // PUT /api/moderator/reports/{id}
    $method === "PUT" && $resource === "reports" && is_numeric($param)
    => updateReport($conn, (int) $param),

    // user management

    // GET /api/moderator/users
    $method === "GET" && $resource === "users" && $param === ""
    => getUsers($conn),

    // POST /api/moderator/users/{id}/warn
    $method === "POST" && $resource === "users" && is_numeric($param) && $subAction === "warn"
    => warnUser($conn, (int) $param),

    // PUT /api/moderator/users/{id}/suspend
    $method === "PUT" && $resource === "users" && is_numeric($param) && $subAction === "suspend"
    => suspendUser($conn, (int) $param),

    // PUT /api/moderator/users/{id}/reinstate
    $method === "PUT" && $resource === "users" && is_numeric($param) && $subAction === "reinstate"
    => reinstateUser($conn, (int) $param),

    default => (function () {
            http_response_code(404);
            echo json_encode(["error" => "Moderator route not found."]);
        })()
};