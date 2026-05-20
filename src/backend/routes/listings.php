<?php
// ============================================================
// listings.php  —  Listing & Category Routes
// Base path: /api/listings  and  /api/categories
// ============================================================

require_once __DIR__ . "/../controller/listingController.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$script = $_SERVER["SCRIPT_NAME"] ?? "";

if ($script && str_starts_with($uri, $script)) {
    $uri = substr($uri, strlen($script));
    if ($uri === false || $uri === "") {
        $uri = "/";
    }
}

$segments = explode("/", trim($uri, "/"));

$base = $segments[1] ?? "";   // "listings" or "categories"
$param = $segments[2] ?? "";
$subAction = $segments[3] ?? "";

// categories
if ($base === "categories") {
    if ($method === "GET") {
        getCategories($conn);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed."]);
    }
    exit;
}

match (true) {

    // GET /api/listings
    $method === "GET" && $param === "" => getListings($conn),

    // GET /api/listings/mine
    $method === "GET" && $param === "mine" => myListings($conn),

    // GET /api/listings/saved
    $method === "GET" && $param === "saved" => getSavedListings($conn),

    // POST /api/listings
    $method === "POST" && $param === "" => createListing($conn),

    // GET /api/listings/{id}
    $method === "GET" && is_numeric($param) && $subAction === ""
    => getListing($conn, (int) $param),

    // PUT /api/listings/{id}
    $method === "PUT" && is_numeric($param) && $subAction === ""
    => updateListing($conn, (int) $param),

    // DELETE /api/listings/{id}
    $method === "DELETE" && is_numeric($param) && $subAction === ""
    => deleteListing($conn, (int) $param),

    // POST /api/listings/{id}/save
    $method === "POST" && is_numeric($param) && $subAction === "save"
    => saveListing($conn, (int) $param),

    // DELETE /api/listings/{id}/save
    $method === "DELETE" && is_numeric($param) && $subAction === "save"
    => unsaveListing($conn, (int) $param),

    default => (function () {
            http_response_code(404);
            echo json_encode(["error" => "Listing route not found."]);
        })()
};