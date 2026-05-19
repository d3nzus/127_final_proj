<?php
// ============================================================
// transactionController.php
// Handles: buy now, confirm, complete, cancel transactions
//          transaction history, reviews, dispute reports
// ============================================================

require_once __DIR__ . "/../config/connector.php";
require_once __DIR__ . "/authLayer.php";
require_once __DIR__ . "/../api/apiHelper.php";

function buyNow($conn)
{
    $session = requireAuth();

    if ($session["user_role"] !== "buyer") {
        http_response_code(403);
        echo json_encode(["error" => "Only buyers can initiate transactions."]);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data["listing_id"])) {
        http_response_code(400);
        echo json_encode(["error" => "listing_id is required."]);
        return;
    }

    $listings = api_fetch_listings();
    $listing = null;
    foreach ($listings as $l) {
        if ((int) $l["id"] === (int) $data["listing_id"]) {
            $listing = $l;
            break;
        }
    }

    if (!$listing || ($listing["moderation_status"] ?? "") !== "approved" || ($listing["listing_status"] ?? "") !== "active") {
        http_response_code(404);
        echo json_encode(["error" => "Listing is not available."]);
        return;
    }

    $users = api_index_by(api_fetch_users(), "id");
    if (empty($users[$listing["seller_id"]]["is_active"])) {
        http_response_code(403);
        echo json_encode(["error" => "This seller's account is currently suspended."]);
        return;
    }

    // Prevent buyer from buying their own listing (if they also have a seller role)
    if ((int) $listing["seller_id"] === (int) $session["user_id"]) {
        http_response_code(400);
        echo json_encode(["error" => "You cannot buy your own listing."]);
        return;
    }

    // Check no existing pending/confirmed transaction for this listing
    $transactions = api_fetch_transactions();
    foreach ($transactions as $t) {
        if ((int) $t["listing_id"] === (int) $data["listing_id"] && in_array($t["status"] ?? "", ["pending", "confirmed"])) {
            http_response_code(409);
            echo json_encode(["error" => "This listing already has an active transaction."]);
            return;
        }
    }

    $conn->prepare("
        INSERT INTO `transaction` (listing_id, buyer_id, seller_id, agreed_price, status)
        VALUES (?, ?, ?, ?, 'pending')
    ")->execute([$listing["id"], $session["user_id"], $listing["seller_id"], $listing["price"]]);

    $txn_id = $conn->lastInsertId();

    // Notify seller
    $conn->prepare("
        INSERT INTO notification (user_id, type, reference_type, reference_id)
        VALUES (?, 'new_order', 'transaction', ?)
    ")->execute([$listing["seller_id"], $txn_id]);

    http_response_code(201);
    echo json_encode([
        "message" => "Order placed. Waiting for seller to confirm.",
        "transaction_id" => (int) $txn_id,
    ]);
}

function confirmTransaction($conn, $id)
{
    $session = requireAuth();

    $txn = getTransactionOrFail($conn, $id);

    if ((int) $txn["seller_id"] !== (int) $session["user_id"]) {
        http_response_code(403);
        echo json_encode(["error" => "Only the seller can confirm this transaction."]);
        return;
    }

    if ($txn["status"] !== "pending") {
        http_response_code(400);
        echo json_encode(["error" => "Transaction is not in pending state."]);
        return;
    }

    $conn->prepare("UPDATE `transaction` SET status = 'confirmed' WHERE id = ?")->execute([$id]);

    // Notify buyer
    $conn->prepare("
        INSERT INTO notification (user_id, type, reference_type, reference_id)
        VALUES (?, 'order_confirmed', 'transaction', ?)
    ")->execute([$txn["buyer_id"], $id]);

    echo json_encode(["message" => "Transaction confirmed. Coordinate meetup with the buyer."]);
}

function completeTransaction($conn, $id)
{
    $session = requireAuth();

    $txn = getTransactionOrFail($conn, $id);

    if ((int) $txn["seller_id"] !== (int) $session["user_id"]) {
        http_response_code(403);
        echo json_encode(["error" => "Only the seller can mark a transaction as completed."]);
        return;
    }

    if ($txn["status"] !== "confirmed") {
        http_response_code(400);
        echo json_encode(["error" => "Transaction must be confirmed before it can be completed."]);
        return;
    }

    $conn->beginTransaction();
    try {
        $conn->prepare("UPDATE `transaction` SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$id]);
        $conn->prepare("UPDATE listing SET listing_status = 'sold' WHERE id = ?")->execute([$txn["listing_id"]]);

        // Notify both parties
        foreach ([$txn["buyer_id"], $txn["seller_id"]] as $uid) {
            $conn->prepare("
                INSERT INTO notification (user_id, type, reference_type, reference_id)
                VALUES (?, 'completed', 'transaction', ?)
            ")->execute([$uid, $id]);
        }

        $conn->commit();
        echo json_encode(["message" => "Transaction marked as completed."]);
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Failed to complete transaction."]);
    }
}

function cancelTransaction($conn, $id)
{
    $session = requireAuth();

    $txn = getTransactionOrFail($conn, $id);

    $is_buyer = (int) $txn["buyer_id"] === (int) $session["user_id"];
    $is_seller = (int) $txn["seller_id"] === (int) $session["user_id"];

    if (!$is_buyer && !$is_seller) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden."]);
        return;
    }

    if (!in_array($txn["status"], ["pending", "confirmed"])) {
        http_response_code(400);
        echo json_encode(["error" => "Only pending or confirmed transactions can be cancelled."]);
        return;
    }

    $conn->prepare("UPDATE `transaction` SET status = 'cancelled' WHERE id = ?")->execute([$id]);
    echo json_encode(["message" => "Transaction cancelled."]);
}

function getTransactions($conn)
{
    $session = requireAuth();
    $uid = $session["user_id"];

    $transactions = api_fetch_transactions();
    $listings = api_index_by(api_fetch_listings(), "id");
    $users = api_index_by(api_fetch_users(), "id");

    $filtered = array_filter($transactions, function ($txn) use ($uid) {
        $role = $_GET["role"] ?? "all";
        if ($role === "buyer") {
            return (int) $txn["buyer_id"] === (int) $uid;
        } elseif ($role === "seller") {
            return (int) $txn["seller_id"] === (int) $uid;
        }
        return (int) $txn["buyer_id"] === (int) $uid || (int) $txn["seller_id"] === (int) $uid;
    });
    if (!empty($_GET["status"])) {
        $filtered = array_filter($filtered, function ($txn) {
            return ($txn["status"] ?? "") === $_GET["status"];
        });
    }

    usort($filtered, function ($a, $b) {
        return strtotime($b["initiated_at"] ?? "0") <=> strtotime($a["initiated_at"] ?? "0");
    });

    $data = array_map(function ($txn) use ($listings, $users) {
        $listing = $listings[$txn["listing_id"]] ?? [];
        $buyer = $users[$txn["buyer_id"]] ?? [];
        $seller = $users[$txn["seller_id"]] ?? [];
        $txn["listing_title"] = $listing["title"] ?? null;
        $txn["item_type"] = $listing["item_type"] ?? null;
        $txn["buyer_fname"] = $buyer["first_name"] ?? null;
        $txn["buyer_lname"] = $buyer["last_name"] ?? null;
        $txn["seller_fname"] = $seller["first_name"] ?? null;
        $txn["seller_lname"] = $seller["last_name"] ?? null;
        return $txn;
    }, $filtered);
    echo json_encode(array_values($data));
}

function getTransaction($conn, $id)
{
    $session = requireAuth();
    $txn = getTransactionOrFail($conn, $id);

    $is_party = (int) $txn["buyer_id"] === (int) $session["user_id"]
        || (int) $txn["seller_id"] === (int) $session["user_id"]
        || $session["user_role"] === "moderator";

    if (!$is_party) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden."]);
        return;
    }

    $listings = api_index_by(api_fetch_listings(), "id");
    $users = api_index_by(api_fetch_users(), "id");
    $txn["listing_title"] = $listings[$txn["listing_id"]]["title"] ?? null;
    $txn["item_type"] = $listings[$txn["listing_id"]]["item_type"] ?? null;
    $txn["buyer_fname"] = $users[$txn["buyer_id"]]["first_name"] ?? null;
    $txn["buyer_lname"] = $users[$txn["buyer_id"]]["last_name"] ?? null;
    $txn["seller_fname"] = $users[$txn["seller_id"]]["first_name"] ?? null;
    $txn["seller_lname"] = $users[$txn["seller_id"]]["last_name"] ?? null;

    echo json_encode($txn);
}


function submitReview($conn, $txn_id)
{
    $session = requireAuth();
    $data = json_decode(file_get_contents("php://input"), true);

    $txn = getTransactionOrFail($conn, $txn_id);

    if ($txn["status"] !== "completed") {
        http_response_code(400);
        echo json_encode(["error" => "Reviews can only be submitted for completed transactions."]);
        return;
    }

    $is_buyer = (int) $txn["buyer_id"] === (int) $session["user_id"];
    $is_seller = (int) $txn["seller_id"] === (int) $session["user_id"];

    if (!$is_buyer && !$is_seller) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden."]);
        return;
    }

    if (empty($data["rating"]) || !in_array($data["rating"], [1, 2, 3, 4, 5])) {
        http_response_code(400);
        echo json_encode(["error" => "rating must be 1-5."]);
        return;
    }

    $reviewer_id = $session["user_id"];
    $reviewee_id = $is_buyer ? $txn["seller_id"] : $txn["buyer_id"];
    $role = $is_buyer ? "buyer" : "seller";

    // Check not already reviewed
    $reviews = api_fetch_reviews();
    foreach ($reviews as $r) {
        if ((int) $r["transaction_id"] === (int) $txn_id && (int) $r["reviewer_id"] === (int) $reviewer_id) {
            http_response_code(409);
            echo json_encode(["error" => "You have already submitted a review for this transaction."]);
            return;
        }
    }

    $conn->prepare("
        INSERT INTO review (transaction_id, reviewer_id, reviewee_id, rating, comment, role)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([$txn_id, $reviewer_id, $reviewee_id, $data["rating"], $data["comment"] ?? null, $role]);

    http_response_code(201);
    echo json_encode(["message" => "Review submitted."]);
}


function fileReport($conn)
{
    $session = requireAuth();
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data["reported_user_id"]) || empty($data["reason"])) {
        http_response_code(400);
        echo json_encode(["error" => "reported_user_id and reason are required."]);
        return;
    }

    $conn->prepare("
        INSERT INTO report (reporter_id, reported_user_id, listing_id, transaction_id, reason, description)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([
                $session["user_id"],
                $data["reported_user_id"],
                $data["listing_id"] ?? null,
                $data["transaction_id"] ?? null,
                $data["reason"],
                $data["description"] ?? null,
            ]);

    http_response_code(201);
    echo json_encode(["message" => "Report submitted. A moderator will review it shortly."]);
}

// helper
function getTransactionOrFail($conn, $id)
{
    $transactions = api_fetch_transactions();
    if ($transactions === null) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to load transaction data from API folder."]);
        exit;
    }
    foreach ($transactions as $txn) {
        if ((int) $txn["id"] === (int) $id) {
            return $txn;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "Transaction not found."]);
    exit;
}