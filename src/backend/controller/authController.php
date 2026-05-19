<?php
// ============================================================
// authController.php
// Handles: register, login, logout, profile view/update
// ============================================================

require_once __DIR__ . "/../config/connector.php";
require_once __DIR__ . "/authLayer.php";


function register($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    $required = ["email", "password", "first_name", "last_name", "role"];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required field: $field"]);
            return;
        }
    }

    // Validate UPV email
    if (!preg_match('/^[a-zA-Z0-9._%+\-]+@up\.edu\.ph$/', $data["email"])) {
        http_response_code(400);
        echo json_encode(["error" => "Only @up.edu.ph email addresses are allowed."]);
        return;
    }

    // Validate role
    $allowed_roles = ["buyer", "seller", "moderator"];
    if (!in_array($data["role"], $allowed_roles)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid role. Must be buyer, seller, or moderator."]);
        return;
    }

    // Validate year_level if provided
    if (!empty($data["year_level"]) && (!is_numeric($data["year_level"]) || $data["year_level"] < 1 || $data["year_level"] > 9)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid year level."]);
        return;
    }

    // Check duplicate email
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->execute([$data["email"]]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["error" => "Email is already registered."]);
        return;
    }

    $password_hash = password_hash($data["password"], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("
        INSERT INTO user (email, password_hash, first_name, last_name, program, year_level, role)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data["email"],
        $password_hash,
        trim($data["first_name"]),
        trim($data["last_name"]),
        $data["program"] ?? null,
        $data["year_level"] ?? null,
        $data["role"],
    ]);

    $new_id = $conn->lastInsertId();

    http_response_code(201);
    echo json_encode([
        "message" => "Registration successful. Please verify your email.",
        "user_id" => (int) $new_id,
    ]);
}


function login($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data["email"]) || empty($data["password"])) {
        http_response_code(400);
        echo json_encode(["error" => "Email and password are required."]);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->execute([$data["email"]]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($data["password"], $user["password_hash"])) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid email or password."]);
        return;
    }

    if (!$user["is_active"]) {
        http_response_code(403);
        echo json_encode(["error" => "Your account has been suspended."]);
        return;
    }

    if (!$user["is_verified"]) {
        http_response_code(403);
        echo json_encode(["error" => "Please verify your email before logging in."]);
        return;
    }

    // Start session
    authStartSession();
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["user_role"] = $user["role"];
    $_SESSION["user_name"] = $user["first_name"] . " " . $user["last_name"];

    unset($user["password_hash"]);
    echo json_encode([
        "message" => "Login successful.",
        "user" => $user,
    ]);
}


function logout()
{
    authStartSession();
    session_destroy();
    echo json_encode(["message" => "Logged out successfully."]);
}


function getProfile($conn)
{
    $session = requireAuth();

    $stmt = $conn->prepare("
        SELECT id, email, first_name, last_name, program, year_level, role,
               is_verified, is_active, profile_photo_url, created_at
        FROM user WHERE id = ?
    ");
    $stmt->execute([$session["user_id"]]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["error" => "User not found."]);
        return;
    }

    echo json_encode($user);
}

function updateProfile($conn)
{
    $session = requireAuth();

    $data = json_decode(file_get_contents("php://input"), true);

    $fields = [];
    $params = [];

    if (!empty($data["first_name"])) {
        $fields[] = "first_name = ?";
        $params[] = trim($data["first_name"]);
    }
    if (!empty($data["last_name"])) {
        $fields[] = "last_name = ?";
        $params[] = trim($data["last_name"]);
    }
    if (array_key_exists("program", $data)) {
        $fields[] = "program = ?";
        $params[] = $data["program"];
    }
    if (array_key_exists("year_level", $data)) {
        $fields[] = "year_level = ?";
        $params[] = $data["year_level"];
    }
    if (array_key_exists("profile_photo_url", $data)) {
        $fields[] = "profile_photo_url = ?";
        $params[] = $data["profile_photo_url"];
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(["error" => "No updatable fields provided."]);
        return;
    }

    $params[] = $session["user_id"];
    $sql = "UPDATE user SET " . implode(", ", $fields) . " WHERE id = ?";
    $conn->prepare($sql)->execute($params);

    echo json_encode(["message" => "Profile updated successfully."]);
}

function changePassword($conn)
{
    $session = requireAuth();

    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data["current_password"]) || empty($data["new_password"])) {
        http_response_code(400);
        echo json_encode(["error" => "current_password and new_password are required."]);
        return;
    }

    $stmt = $conn->prepare("SELECT password_hash FROM user WHERE id = ?");
    $stmt->execute([$session["user_id"]]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($data["current_password"], $user["password_hash"])) {
        http_response_code(401);
        echo json_encode(["error" => "Current password is incorrect."]);
        return;
    }

    if (strlen($data["new_password"]) < 8) {
        http_response_code(400);
        echo json_encode(["error" => "New password must be at least 8 characters."]);
        return;
    }

    $new_hash = password_hash($data["new_password"], PASSWORD_BCRYPT);
    $conn->prepare("UPDATE user SET password_hash = ? WHERE id = ?")->execute([$new_hash, $session["user_id"]]);

    echo json_encode(["message" => "Password changed successfully."]);
}