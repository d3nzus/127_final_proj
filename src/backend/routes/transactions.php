<?php
// ============================================================
// transactions.php  —  Transaction & Report Routes
// ============================================================

require_once __DIR__ . "/../controller/transactionController.php";

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


$base = $segments[1] ?? "";   // "transactions" | "reports"
$param = $segments[2] ?? "";   // numeric id | ""
$subAction = $segments[3] ?? "";   // confirm | complete | cancel | reviews | ""

// reports
if ($base === "reports") {
    if ($method === "POST") {
        fileReport($conn);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed."]);
    }
    exit;
}

// transactions
match (true) {

    // POST /api/transactions  (buy now)
    $method === "POST" && $param === ""
    => buyNow($conn),

    // GET /api/transactions
    $method === "GET" && $param === ""
    => getTransactions($conn),

    // GET /api/transactions/{id}
    $method === "GET" && is_numeric($param) && $subAction === ""
    => getTransaction($conn, (int) $param),

    // PUT /api/transactions/{id}/confirm
    $method === "PUT" && is_numeric($param) && $subAction === "confirm"
    => confirmTransaction($conn, (int) $param),

    // PUT /api/transactions/{id}/complete
    $method === "PUT" && is_numeric($param) && $subAction === "complete"
    => completeTransaction($conn, (int) $param),

    // PUT /api/transactions/{id}/cancel
    $method === "PUT" && is_numeric($param) && $subAction === "cancel"
    => cancelTransaction($conn, (int) $param),

    // POST /api/transactions/{id}/reviews
    $method === "POST" && is_numeric($param) && $subAction === "reviews"
    => submitReview($conn, (int) $param),

    default => (function () {
            http_response_code(404);
            echo json_encode(["error" => "Transaction route not found."]);
        })()
};