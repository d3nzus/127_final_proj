<?php

function api_include_json($scriptPath) {
    if (!is_file($scriptPath)) {
        return null;
    }
    global $conn;
    

    $cwd = getcwd();
    chdir(dirname($scriptPath));

    ob_start();
    include $scriptPath;
    $json = ob_get_clean();

    chdir($cwd);

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    return $data;
}

function api_index_by(array $items, string $key) {
    $indexed = [];
    foreach ($items as $item) {
        if (isset($item[$key])) {
            $indexed[$item[$key]] = $item;
        }
    }
    return $indexed;
}

function api_group_by(array $items, string $key) {
    $grouped = [];
    foreach ($items as $item) {
        if (!isset($item[$key])) {
            continue;
        }
        $grouped[$item[$key]][] = $item;
    }
    return $grouped;
}

function api_fetch_categories() {
    return api_include_json(__DIR__ . "/category.php") ?? [];
}

function api_fetch_users() {
    return api_include_json(__DIR__ . "/user.php") ?? [];
}

function api_fetch_transactions() {
    return api_include_json(__DIR__ . "/transaction.php") ?? [];
}

function api_fetch_listings() {
    return api_include_json(__DIR__ . "/listing.php") ?? [];
}

function api_fetch_reports() {
    return api_include_json(__DIR__ . "/report.php") ?? [];
}

function api_fetch_notifications() {
    return api_include_json(__DIR__ . "/notification.php") ?? [];
}

function api_fetch_saved_listings() {
    return api_include_json(__DIR__ . "/saved_listing.php") ?? [];
}

function api_fetch_listing_images() {
    return api_include_json(__DIR__ . "/listing_image") ?? [];
}

function api_fetch_reviews() {
    return api_include_json(__DIR__ . "/review.php") ?? [];
}

function api_fetch_user_warnings() {
    return api_include_json(__DIR__ . "/user_warning.php") ?? [];
}

function api_fetch_consumable_details() {
    return api_include_json(__DIR__ . "/consumable_detail.php") ?? [];
}

function api_fetch_non_consumable_details() {
    return api_include_json(__DIR__ . "/non_consumable_detail.php") ?? [];
}
