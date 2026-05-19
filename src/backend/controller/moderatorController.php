<?php
// ============================================================
// moderatorController.php
// Handles: listing moderation, report management,
//          user warnings & suspension, dashboard summary
// Role required: moderator
// ============================================================

require_once __DIR__ . "/../config/connector.php";
require_once __DIR__ . "/authLayer.php";
require_once __DIR__ . "/../api/apiHelper.php";

// DASHBOARD SUMMARY
function getDashboard($conn)
{
    requireModerator();

    $data = [];

    // Pending listings
    $data["pending_listings"] = (int) $conn->query(
        "SELECT COUNT(*) FROM listing WHERE moderation_status = 'pending'"
    )->fetchColumn();

    // Active reports
    $data["open_reports"] = (int) $conn->query(
        "SELECT COUNT(*) FROM report WHERE status IN ('open','reviewing')"
    )->fetchColumn();

    // Suspended users
    $data["suspended_users"] = (int) $conn->query(
        "SELECT COUNT(*) FROM user WHERE is_active = 0"
    )->fetchColumn();

    // Unverified users
    $data["unverified_users"] = (int) $conn->query(
        "SELECT COUNT(*) FROM user WHERE is_verified = 0"
    )->fetchColumn();

    // Total active listings
    $data["active_listings"] = (int) $conn->query(
        "SELECT COUNT(*) FROM listing WHERE moderation_status='approved' AND listing_status='active'"
    )->fetchColumn();

    // Total completed transactions
    $data["completed_transactions"] = (int) $conn->query(
        "SELECT COUNT(*) FROM `transaction` WHERE status = 'completed'"
    )->fetchColumn();

    echo json_encode($data);
}

function getPendingListings($conn)
{
    requireModerator();

    $listings = array_filter(api_fetch_listings(), function ($listing) {
        return ($listing["moderation_status"] ?? "") === "pending";
    });
    $categories = api_index_by(api_fetch_categories(), "id");
    $users = api_index_by(api_fetch_users(), "id");
    $images = api_group_by(api_fetch_listing_images(), "listing_id");

    usort($listings, function ($a, $b) {
        return strtotime($a["created_at"] ?? "0") <=> strtotime($b["created_at"] ?? "0");
    });

    $data = array_map(function ($listing) use ($categories, $users, $images) {
        $entry = api_enrich_listing($listing, $categories, $users, $images);
        return $entry;
    }, $listings);

    echo json_encode(array_values($data));
}

function getAllListingsMod($conn)
{
    requireModerator();

    $listings = api_fetch_listings();
    $categories = api_index_by(api_fetch_categories(), "id");
    $users = api_index_by(api_fetch_users(), "id");
    $images = api_group_by(api_fetch_listing_images(), "listing_id");

    $filtered = array_filter($listings, function ($listing) {
        if (!empty($_GET["moderation_status"]) && ($listing["moderation_status"] ?? "") !== $_GET["moderation_status"]) {
            return false;
        }
        if (!empty($_GET["listing_status"]) && ($listing["listing_status"] ?? "") !== $_GET["listing_status"]) {
            return false;
        }
        if (!empty($_GET["seller_id"]) && (int) $listing["seller_id"] !== (int) $_GET["seller_id"]) {
            return false;
        }
        return true;
    });

    usort($filtered, function ($a, $b) {
        return strtotime($b["created_at"] ?? "0") <=> strtotime($a["created_at"] ?? "0");
    });

    $data = array_map(function ($listing) use ($categories, $users, $images) {
        return api_enrich_listing($listing, $categories, $users, $images);
    }, $filtered);

    echo json_encode(array_values($data));
}

function approveListing($conn, $id)
{
    $session = requireModerator();

    $listing = getListingOrFail($conn, $id);

    if ($listing["moderation_status"] !== "pending") {
        http_response_code(400);
        echo json_encode(["error" => "Only pending listings can be approved."]);
        return;
    }

    $conn->prepare("
        UPDATE listing
        SET moderation_status = 'approved',
            moderated_by = ?,
            moderated_at = NOW(),
            moderation_feedback = NULL
        WHERE id = ?
    ")->execute([$session["user_id"], $id]);

    // Notify seller
    $conn->prepare("
        INSERT INTO notification (user_id, type, reference_type, reference_id)
        VALUES (?, 'listing_approved', 'listing', ?)
    ")->execute([$listing["seller_id"], $id]);

    echo json_encode(["message" => "Listing approved and published."]);
}

function rejectListing($conn, $id)
{
    $session = requireModerator();
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data["feedback"])) {
        http_response_code(400);
        echo json_encode(["error" => "Rejection feedback is required."]);
        return;
    }

    $listing = getListingOrFail($conn, $id);

    if ($listing["moderation_status"] !== "pending") {
        http_response_code(400);
        echo json_encode(["error" => "Only pending listings can be rejected."]);
        return;
    }

    $conn->prepare("
        UPDATE listing
        SET moderation_status = 'rejected',
            moderated_by = ?,
            moderated_at = NOW(),
            moderation_feedback = ?
        WHERE id = ?
    ")->execute([$session["user_id"], $data["feedback"], $id]);

    // Notify seller
    $conn->prepare("
        INSERT INTO notification (user_id, type, reference_type, reference_id)
        VALUES (?, 'listing_rejected', 'listing', ?)
    ")->execute([$listing["seller_id"], $id]);

    echo json_encode(["message" => "Listing rejected with feedback."]);
}

function moderatorEditListing($conn, $id)
{
    requireModerator();
    getListingOrFail($conn, $id);

    $data = json_decode(file_get_contents("php://input"), true);
    $fields = [];
    $params = [];

    foreach (["title", "description", "price", "condition", "meetup_location", "color_texture_notes", "category_id", "listing_status", "moderation_status"] as $col) {
        if (array_key_exists($col, $data)) {
            $fields[] = "`$col` = ?";
            $params[] = $data[$col];
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(["error" => "No fields to update."]);
        return;
    }

    $params[] = $id;
    $conn->prepare("UPDATE listing SET " . implode(", ", $fields) . " WHERE id = ?")->execute($params);
    echo json_encode(["message" => "Listing updated by moderator."]);
}

function moderatorRemoveListing($conn, $id)
{
    requireModerator();
    getListingOrFail($conn, $id);
    $conn->prepare("UPDATE listing SET listing_status = 'removed' WHERE id = ?")->execute([$id]);
    echo json_encode(["message" => "Listing removed by moderator."]);
}

function getReports($conn)
{
    requireModerator();

    $reports = api_fetch_reports();
    $users = api_index_by(api_fetch_users(), "id");

    $filtered = array_filter($reports, function ($report) {
        if (!empty($_GET["status"]) && ($report["status"] ?? "") !== $_GET["status"]) {
            return false;
        }
        return true;
    });

    usort($filtered, function ($a, $b) {
        return strtotime($b["created_at"] ?? "0") <=> strtotime($a["created_at"] ?? "0");
    });

    $data = [];
    foreach ($filtered as $report) {
        $reporter = $users[$report["reporter_id"]] ?? [];
        $reported = $users[$report["reported_user_id"]] ?? [];
        $report["reporter_fname"] = $reporter["first_name"] ?? null;
        $report["reporter_lname"] = $reporter["last_name"] ?? null;
        $report["reported_fname"] = $reported["first_name"] ?? null;
        $report["reported_lname"] = $reported["last_name"] ?? null;
        $data[] = $report;
    }

    echo json_encode($data);
}

function updateReport($conn, $id)
{
    requireModerator();
    $data = json_decode(file_get_contents("php://input"), true);

    $allowed = ["reviewing", "resolved", "closed"];
    if (empty($data["status"]) || !in_array($data["status"], $allowed)) {
        http_response_code(400);
        echo json_encode(["error" => "status must be: reviewing, resolved, or closed."]);
        return;
    }

    $report = getReportOrFail($conn, $id);

    $resolved_at = in_array($data["status"], ["resolved", "closed"]) ? "NOW()" : "NULL";

    $conn->prepare("
        UPDATE report
        SET status = ?, resolved_at = $resolved_at
        WHERE id = ?
    ")->execute([$data["status"], $id]);

    // Notify both parties on resolution
    if (in_array($data["status"], ["resolved", "closed"])) {
        foreach ([$report["reporter_id"], $report["reported_user_id"]] as $uid) {
            $conn->prepare("
                INSERT INTO notification (user_id, type, reference_type, reference_id)
                VALUES (?, 'dispute_update', 'report', ?)
            ")->execute([$uid, $id]);
        }
    }

    echo json_encode(["message" => "Report updated."]);
}

function warnUser($conn, $user_id)
{
    $session = requireModerator();
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data["reason"])) {
        http_response_code(400);
        echo json_encode(["error" => "Reason is required."]);
        return;
    }

    getUserOrFail($conn, $user_id);

    $conn->prepare("
        INSERT INTO user_warning (user_id, issued_by, reason)
        VALUES (?, ?, ?)
    ")->execute([$user_id, $session["user_id"], $data["reason"]]);

    echo json_encode(["message" => "Warning issued to user."]);
}

function suspendUser($conn, $user_id)
{
    requireModerator();
    getUserOrFail($conn, $user_id);

    $conn->prepare("UPDATE user SET is_active = 0 WHERE id = ?")->execute([$user_id]);

    // Remove all active listings by this user
    $conn->prepare("
        UPDATE listing SET listing_status = 'removed'
        WHERE seller_id = ? AND listing_status = 'active'
    ")->execute([$user_id]);

    echo json_encode(["message" => "User suspended and active listings removed."]);
}

function reinstateUser($conn, $user_id)
{
    requireModerator();
    getUserOrFail($conn, $user_id);
    $conn->prepare("UPDATE user SET is_active = 1 WHERE id = ?")->execute([$user_id]);
    echo json_encode(["message" => "User reinstated."]);
}

function getUsers($conn)
{
    requireModerator();

    $users = api_fetch_users();
    $userWarnings = api_fetch_user_warnings();
    $warningCounts = [];
    foreach ($userWarnings as $warning) {
        $warningCounts[$warning["user_id"]] = ($warningCounts[$warning["user_id"]] ?? 0) + 1;
    }

    $filtered = array_filter($users, function ($user) {
        if (isset($_GET["is_active"]) && (int) $user["is_active"] !== (int) $_GET["is_active"]) {
            return false;
        }
        if (isset($_GET["is_verified"]) && (int) $user["is_verified"] !== (int) $_GET["is_verified"]) {
            return false;
        }
        if (!empty($_GET["role"]) && $user["role"] !== $_GET["role"]) {
            return false;
        }
        return true;
    });

    usort($filtered, function ($a, $b) {
        return strtotime($b["created_at"] ?? "0") <=> strtotime($a["created_at"] ?? "0");
    });

    $data = array_map(function ($user) use ($warningCounts) {
        $user["warning_count"] = $warningCounts[$user["id"]] ?? 0;
        return $user;
    }, $filtered);

    echo json_encode(array_values($data));
}

function getListingOrFail($conn, $id)
{
    $listings = api_fetch_listings();
    if ($listings === null) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to load listing data from API folder."]);
        exit;
    }
    foreach ($listings as $listing) {
        if ((int) $listing["id"] === (int) $id) {
            return $listing;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "Listing not found."]);
    exit;
}

function getUserOrFail($conn, $id)
{
    $users = api_fetch_users();
    if ($users === null) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to load user data from API folder."]);
        exit;
    }
    foreach ($users as $user) {
        if ((int) $user["id"] === (int) $id) {
            return $user;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "User not found."]);
    exit;
}

function getReportOrFail($conn, $id)
{
    $reports = api_fetch_reports();
    if ($reports === null) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to load report data from API folder."]);
        exit;
    }
    foreach ($reports as $report) {
        if ((int) $report["id"] === (int) $id) {
            return $report;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "Report not found."]);
    exit;
}