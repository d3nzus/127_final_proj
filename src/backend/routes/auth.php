<?php
// ============================================================
// auth.php  —  Auth Routes
// Base path: /api/auth
// ============================================================

require_once __DIR__ . "/../controllers/authController.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

// Parse the last path segment after /api/auth/
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$segments = explode("/", trim($uri, "/"));


$action = $segments[2] ?? "";

match (true) {
    // POST /api/auth/register
    $method === "POST" && $action === "register" => register($conn),

    // POST /api/auth/login
    $method === "POST" && $action === "login" => login($conn),

    // POST /api/auth/logout
    $method === "POST" && $action === "logout" => logout(),

    // GET  /api/auth/profile
    $method === "GET" && $action === "profile" => getProfile($conn),

    // PUT  /api/auth/profile
    $method === "PUT" && $action === "profile" => updateProfile($conn),

    // PUT  /api/auth/password
    $method === "PUT" && $action === "password" => changePassword($conn),

    default => (function () {
            http_response_code(404);
            echo json_encode(["error" => "Auth route not found."]);
        })()
};