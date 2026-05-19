<?php
// ============================================================
// authLayer.php
// Centralized authentication helpers for controllers
// ============================================================

function authStartSession() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function requireAuth() {
    authStartSession();

    if (empty($_SESSION["user_id"])) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized."]);
        exit;
    }
    return $_SESSION;
}

function requireModerator() {
    $session = requireAuth();
    if ($session["user_role"] !== "moderator") {
        http_response_code(403);
        echo json_encode(["error" => "Moderator access required."]);
        exit;
    }
    return $session;
}

function getCurrentUserId() {
    $session = requireAuth();
    return $session["user_id"];
}

function getCurrentUserRole() {
    $session = requireAuth();
    return $session["user_role"];
}
