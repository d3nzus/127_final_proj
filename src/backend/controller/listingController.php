<?php
// ============================================================
// listingController.php
// Handles: browse, get, create, update, delete listings
//          consumable & non-consumable details, saved listings
// ============================================================

require_once __DIR__ . "/../config/connector.php";
require_once __DIR__ . "/authLayer.php";
require_once __DIR__ . "/../api/apiHelper.php";

function api_enrich_listing(array $listing, array $categoriesById, array $usersById, array $imagesByListingId)
{
    $category = $categoriesById[$listing["category_id"]] ?? null;
    $seller = $usersById[$listing["seller_id"]] ?? null;

    $listing["category_name"] = $category["name"] ?? null;
    $listing["first_name"] = $seller["first_name"] ?? null;
    $listing["last_name"] = $seller["last_name"] ?? null;
    $listing["profile_photo_url"] = $seller["profile_photo_url"] ?? null;

    $images = $imagesByListingId[$listing["id"]] ?? [];
    if (!empty($images)) {
        usort($images, function ($a, $b) {
            return ($a["sort_order"] ?? 0) <=> ($b["sort_order"] ?? 0);
        });
        $listing["cover_image"] = $images[0]["image_url"] ?? null;
    } else {
        $listing["cover_image"] = null;
    }

    return $listing;
}

function getListings($conn)
{
    $listings = api_fetch_listings();
    $categories = api_index_by(api_fetch_categories(), "id");
    $users = api_index_by(api_fetch_users(), "id");
    $images = api_group_by(api_fetch_listing_images(), "listing_id");

    $filtered = array_filter($listings, function ($listing) {
        if (($listing["moderation_status"] ?? "") !== "approved") {
            return false;
        }
        if (($listing["listing_status"] ?? "") !== "active") {
            return false;
        }

        if (!empty($_GET["category_id"]) && (int) $listing["category_id"] !== (int) $_GET["category_id"]) {
            return false;
        }
        if (!empty($_GET["item_type"]) && in_array($_GET["item_type"], ["consumable", "non_consumable"])) {
            if ($listing["item_type"] !== $_GET["item_type"]) {
                return false;
            }
        }
        if (!empty($_GET["min_price"]) && (float) $listing["price"] < (float) $_GET["min_price"]) {
            return false;
        }
        if (!empty($_GET["max_price"]) && (float) $listing["price"] > (float) $_GET["max_price"]) {
            return false;
        }

        return true;
    });

    $sortDesc = (!empty($_GET["sort"]) && $_GET["sort"] === "oldest") ? false : true;
    usort($filtered, function ($a, $b) use ($sortDesc) {
        $tsA = strtotime($a["created_at"] ?? "0");
        $tsB = strtotime($b["created_at"] ?? "0");
        return $sortDesc ? $tsB <=> $tsA : $tsA <=> $tsB;
    });

    $limit = isset($_GET["limit"]) ? max(1, min(50, (int) $_GET["limit"])) : 20;
    $page = isset($_GET["page"]) ? max(1, (int) $_GET["page"]) : 1;
    $total = count($filtered);
    $offset = ($page - 1) * $limit;
    $paged = array_slice($filtered, $offset, $limit);

    $data = array_map(function ($listing) use ($categories, $users, $images) {
        return api_enrich_listing($listing, $categories, $users, $images);
    }, $paged);

    echo json_encode([
        "data" => array_values($data),
        "total" => $total,
        "page" => $page,
        "limit" => $limit,
        "total_pages" => (int) ceil($total / $limit),
    ]);
}

function getListing($conn, $id)
{
    $listings = api_fetch_listings();
    $listing = null;
    foreach ($listings as $item) {
        if ((int) $item["id"] === (int) $id) {
            $listing = $item;
            break;
        }
    }

    if (!$listing) {
        http_response_code(404);
        echo json_encode(["error" => "Listing not found."]);
        return;
    }

    $categories = api_index_by(api_fetch_categories(), "id");
    $users = api_index_by(api_fetch_users(), "id");
    $imagesByListingId = api_group_by(api_fetch_listing_images(), "listing_id");
    $listing = api_enrich_listing($listing, $categories, $users, $imagesByListingId);

    if ($listing["item_type"] === "consumable") {
        $details = api_fetch_consumable_details();
        foreach ($details as $detail) {
            if ((int) $detail["listing_id"] === (int) $id) {
                $listing["consumable_detail"] = $detail;
                break;
            }
        }
    } else {
        $details = api_fetch_non_consumable_details();
        foreach ($details as $detail) {
            if ((int) $detail["listing_id"] === (int) $id) {
                $listing["non_consumable_detail"] = $detail;
                break;
            }
        }
    }

    $listing["images"] = $imagesByListingId[$id] ?? [];
    usort($listing["images"], function ($a, $b) {
        return ($a["sort_order"] ?? 0) <=> ($b["sort_order"] ?? 0);
    });

    echo json_encode($listing);
}

function createListing($conn)
{
    $session = requireAuth();
    if ($session["user_role"] !== "seller") {
        http_response_code(403);
        echo json_encode(["error" => "Only sellers can create listings."]);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    $required = ["title", "price", "category_id", "item_type", "condition"];
    foreach ($required as $f) {
        if (!isset($data[$f]) || $data[$f] === "") {
            http_response_code(400);
            echo json_encode(["error" => "Missing required field: $f"]);
            return;
        }
    }

    if (!in_array($data["item_type"], ["consumable", "non_consumable"])) {
        http_response_code(400);
        echo json_encode(["error" => "item_type must be consumable or non_consumable."]);
        return;
    }

    if (!is_numeric($data["price"]) || $data["price"] <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "price must be a positive number."]);
        return;
    }

    // Verify category exists via API folder
    $categories = api_fetch_categories();
    $validCategory = false;
    foreach ($categories as $category) {
        if ((int) $category["id"] === (int) $data["category_id"]) {
            $validCategory = true;
            break;
        }
    }
    if (!$validCategory) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid category_id."]);
        return;
    }

    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("
            INSERT INTO listing
              (seller_id, category_id, title, description, price, item_type,
               `condition`, meetup_location, color_texture_notes,
               moderation_status, listing_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'active')
        ");
        $stmt->execute([
            $session["user_id"],
            $data["category_id"],
            trim($data["title"]),
            $data["description"] ?? null,
            $data["price"],
            $data["item_type"],
            $data["condition"],
            $data["meetup_location"] ?? null,
            $data["color_texture_notes"] ?? null,
        ]);
        $listing_id = $conn->lastInsertId();

        // Insert type-specific details
        if ($data["item_type"] === "consumable") {
            $conn->prepare("
                INSERT INTO consumable_detail (listing_id, estimated_remaining, date_opened, expiry_date)
                VALUES (?, ?, ?, ?)
            ")->execute([
                        $listing_id,
                        $data["estimated_remaining"] ?? null,
                        $data["date_opened"] ?? null,
                        $data["expiry_date"] ?? null,
                    ]);
        } else {
            $conn->prepare("
                INSERT INTO non_consumable_detail
                  (listing_id, size_or_dimensions, material, duration_of_use, known_damages, quantity)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([
                        $listing_id,
                        $data["size_or_dimensions"] ?? null,
                        $data["material"] ?? null,
                        $data["duration_of_use"] ?? null,
                        $data["known_damages"] ?? null,
                        $data["quantity"] ?? 1,
                    ]);
        }

        $conn->commit();

        http_response_code(201);
        echo json_encode([
            "message" => "Listing submitted for moderation.",
            "listing_id" => (int) $listing_id,
        ]);
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Failed to create listing: " . $e->getMessage()]);
    }
}

function updateListing($conn, $id)
{
    $session = requireAuth();

    $listing = null;
    foreach (api_fetch_listings() as $item) {
        if ((int) $item["id"] === (int) $id) {
            $listing = $item;
            break;
        }
    }

    if (!$listing) {
        http_response_code(404);
        echo json_encode(["error" => "Listing not found."]);
        return;
    }

    // Only the owner can edit (unless moderator — that's in moderatorController)
    if ($session["user_role"] !== "moderator" && (int) $listing["seller_id"] !== (int) $session["user_id"]) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden."]);
        return;
    }

    // Sellers can only edit pending or rejected listings
    if ($session["user_role"] === "seller" && $listing["moderation_status"] === "approved") {
        http_response_code(403);
        echo json_encode(["error" => "Approved listings cannot be edited. Please contact a moderator."]);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $fields = [];
    $params = [];

    foreach (["title", "description", "price", "condition", "meetup_location", "color_texture_notes", "category_id"] as $col) {
        if (array_key_exists($col, $data)) {
            $fields[] = "`$col` = ?";
            $params[] = $data[$col];
        }
    }

    // Re-submit for moderation when seller edits a rejected listing
    if ($session["user_role"] === "seller" && $listing["moderation_status"] === "rejected") {
        $fields[] = "moderation_status = 'pending'";
        $fields[] = "moderation_feedback = NULL";
        $fields[] = "moderated_by = NULL";
        $fields[] = "moderated_at = NULL";
    }

    if (!empty($fields)) {
        $params[] = $id;
        $conn->prepare("UPDATE listing SET " . implode(", ", $fields) . " WHERE id = ?")->execute($params);
    }

    // Update type-specific details
    if ($listing["item_type"] === "consumable") {
        $consFields = [];
        $consParams = [];
        foreach (["estimated_remaining", "date_opened", "expiry_date"] as $col) {
            if (array_key_exists($col, $data)) {
                $consFields[] = "$col = ?";
                $consParams[] = $data[$col];
            }
        }
        if (!empty($consFields)) {
            $consParams[] = $id;
            $conn->prepare("UPDATE consumable_detail SET " . implode(", ", $consFields) . " WHERE listing_id = ?")->execute($consParams);
        }
    } else {
        $ncFields = [];
        $ncParams = [];
        foreach (["size_or_dimensions", "material", "duration_of_use", "known_damages", "quantity"] as $col) {
            if (array_key_exists($col, $data)) {
                $ncFields[] = "$col = ?";
                $ncParams[] = $data[$col];
            }
        }
        if (!empty($ncFields)) {
            $ncParams[] = $id;
            $conn->prepare("UPDATE non_consumable_detail SET " . implode(", ", $ncFields) . " WHERE listing_id = ?")->execute($ncParams);
        }
    }

    echo json_encode(["message" => "Listing updated successfully."]);
}

function deleteListing($conn, $id)
{
    $session = requireAuth();

    $listing = null;
    foreach (api_fetch_listings() as $item) {
        if ((int) $item["id"] === (int) $id) {
            $listing = $item;
            break;
        }
    }

    if (!$listing) {
        http_response_code(404);
        echo json_encode(["error" => "Listing not found."]);
        return;
    }

    if ($session["user_role"] !== "moderator" && (int) $listing["seller_id"] !== (int) $session["user_id"]) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden."]);
        return;
    }

    $conn->prepare("UPDATE listing SET listing_status = 'removed' WHERE id = ?")->execute([$id]);
    echo json_encode(["message" => "Listing removed."]);
}

function myListings($conn)
{
    $session = requireAuth();

    $listings = api_fetch_listings();
    $categories = api_index_by(api_fetch_categories(), "id");
    $imagesByListingId = api_group_by(api_fetch_listing_images(), "listing_id");
    $users = api_index_by(api_fetch_users(), "id");

    $filtered = array_filter($listings, function ($listing) use ($session) {
        return (int) $listing["seller_id"] === (int) $session["user_id"];
    });

    usort($filtered, function ($a, $b) {
        return strtotime($b["created_at"] ?? "0") <=> strtotime($a["created_at"] ?? "0");
    });

    $data = array_map(function ($listing) use ($categories, $imagesByListingId, $users) {
        return api_enrich_listing($listing, $categories, $users, $imagesByListingId);
    }, $filtered);

    echo json_encode(array_values($data));
}

function getSavedListings($conn)
{
    $session = requireAuth();

    $savedListings = api_fetch_saved_listings();
    $savedByUser = array_filter($savedListings, function ($row) use ($session) {
        return (int) $row["user_id"] === (int) $session["user_id"];
    });

    $listings = api_index_by(api_fetch_listings(), "id");
    $categories = api_index_by(api_fetch_categories(), "id");
    $imagesByListingId = api_group_by(api_fetch_listing_images(), "listing_id");
    $users = api_index_by(api_fetch_users(), "id");

    $data = [];
    foreach ($savedByUser as $saved) {
        $listing = $listings[$saved["listing_id"]] ?? null;
        if (!$listing) {
            continue;
        }
        $data[] = api_enrich_listing($listing, $categories, $users, $imagesByListingId);
    }

    echo json_encode(array_values($data));
}

function saveListing($conn, $listing_id)
{
    $session = requireAuth();

    $listings = api_fetch_listings();
    $listing = null;
    foreach ($listings as $item) {
        if ((int) $item["id"] === (int) $listing_id) {
            $listing = $item;
            break;
        }
    }

    if (!$listing || $listing["moderation_status"] !== "approved" || $listing["listing_status"] !== "active") {
        http_response_code(404);
        echo json_encode(["error" => "Listing not available."]);
        return;
    }

    try {
        $conn->prepare("INSERT INTO saved_listing (user_id, listing_id) VALUES (?,?)")->execute([$session["user_id"], $listing_id]);
        echo json_encode(["message" => "Listing saved."]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "Already saved."]);
    }
}

function unsaveListing($conn, $listing_id)
{
    $session = requireAuth();
    $conn->prepare("DELETE FROM saved_listing WHERE user_id = ? AND listing_id = ?")->execute([$session["user_id"], $listing_id]);
    echo json_encode(["message" => "Listing removed from saved."]);
}

function getCategories($conn)
{
    $categories = api_fetch_categories();
    if ($categories === null) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to load categories from API folder."]);
        return;
    }

    echo json_encode($categories);
}