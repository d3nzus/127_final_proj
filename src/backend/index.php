<?php
// ============================================================
// index.php — UPCart API Entry Point
// ============================================================

header("Content-Type: application/json");

// Error handler to catch fatal errors and return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        "error" => $errstr,
        "file" => $errfile,
        "line" => $errline,
        "errno" => $errno
    ]);
    exit;
});

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
    exit;
});

require_once __DIR__ . "/config/connector.php";

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$script = $_SERVER["SCRIPT_NAME"] ?? "";

if ($script && str_starts_with($uri, $script)) {
    $uri = substr($uri, strlen($script));
    if ($uri === false || $uri === "") {
        $uri = "/";
    }
}

if (strpos($uri, "/api/auth") === 0) {
    require_once __DIR__ . "/routes/auth.php";
    exit;
}

if (strpos($uri, "/api/categories") === 0 || strpos($uri, "/api/listings") === 0) {
    require_once __DIR__ . "/routes/listings.php";
    exit;
}

if (strpos($uri, "/api/moderator") === 0) {
    require_once __DIR__ . "/routes/moderator.php";
    exit;
}

if (strpos($uri, "/api/transactions") === 0 || strpos($uri, "/api/reports") === 0) {
    require_once __DIR__ . "/routes/transactions.php";
    exit;
}

http_response_code(404);
echo json_encode(["error" => "Endpoint not found"]);
