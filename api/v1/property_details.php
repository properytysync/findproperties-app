<?php
require_once __DIR__ . "/_helpers.php";

header("Content-Type: application/json; charset=utf-8");

$pid = get_int("pid", 0);

if ($pid <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid property ID"
    ]);
    exit;
}

$query = mysqli_query(
    $con,
    "SELECT property.*, user.* 
     FROM property 
     LEFT JOIN user ON property.uid = user.uid 
     WHERE property.pid = {$pid}
     LIMIT 1"
);

if (!$query) {
    echo json_encode([
        "status" => "error",
        "message" => "Property query failed: " . mysqli_error($con)
    ]);
    exit;
}

$property = mysqli_fetch_assoc($query);

if (!$property) {
    echo json_encode([
        "status" => "error",
        "message" => "Property not found"
    ]);
    exit;
}

mysqli_query($con, "UPDATE property SET views = views + 1 WHERE pid = {$pid}");

$siteInfoQuery = mysqli_query(
    $con,
    "SELECT id, logo_path, banner_image_path, currency, banner_writeup, favicon_path,
            welcome_message, display_mode, page_type, paystack_public_key, paystack_secret_key,
            viewing_fee, enable_viewing_payment
     FROM site_info
     WHERE id = 1
     LIMIT 1"
);

$siteInfo = mysqli_fetch_assoc($siteInfoQuery) ?: [];

function property_images_to_urls(array $row): array {
    $images = [];
    foreach (["pimage", "pimage1", "pimage2", "pimage3", "pimage4"] as $field) {
        if (!empty($row[$field])) {
            $images[] = to_public_url("admin/property/" . ltrim($row[$field], "/"));
        }
    }
    return array_values(array_filter($images));
}

function map_images_to_urls(array $row): array {
    $images = [];
    foreach (["mapimage", "topmapimage", "groundmapimage"] as $field) {
        if (!empty($row[$field])) {
            $images[] = to_public_url("admin/property/" . ltrim($row[$field], "/"));
        }
    }
    return array_values(array_filter($images));
}

$property["images"] = property_images_to_urls($property);
$property["map_images"] = map_images_to_urls($property);

$current_location = mysqli_real_escape_string($con, (string)($property["location"] ?? ""));
$current_city     = mysqli_real_escape_string($con, (string)($property["city"] ?? ""));
$current_state    = mysqli_real_escape_string($con, (string)($property["state"] ?? ""));
$current_type     = mysqli_real_escape_string($con, (string)($property["type"] ?? ""));
$current_stype    = mysqli_real_escape_string($con, (string)($property["stype"] ?? ""));

$similarSql = "
    SELECT *,
    (
        CASE WHEN location = '{$current_location}' THEN 50 ELSE 0 END +
        CASE WHEN city = '{$current_city}' THEN 30 ELSE 0 END +
        CASE WHEN state = '{$current_state}' THEN 20 ELSE 0 END +
        CASE WHEN type = '{$current_type}' THEN 40 ELSE 0 END +
        CASE WHEN stype = '{$current_stype}' THEN 35 ELSE 0 END
    ) AS relevance_score
    FROM property
    WHERE pid != {$pid}
      AND status = 'available'
      AND (
        location = '{$current_location}' OR
        city = '{$current_city}' OR
        state = '{$current_state}' OR
        type = '{$current_type}' OR
        stype = '{$current_stype}'
      )
    ORDER BY relevance_score DESC, date DESC
    LIMIT 3
";

$similar = [];
$similarResult = mysqli_query($con, $similarSql);

if ($similarResult) {
    while ($row = mysqli_fetch_assoc($similarResult)) {
        $row["images"] = property_images_to_urls($row);
        $row["map_images"] = map_images_to_urls($row);
        $similar[] = $row;
    }
}

if (empty($similar)) {
    $fallback = mysqli_query(
        $con,
        "SELECT * FROM property WHERE pid != {$pid} AND status = 'available' ORDER BY RAND() LIMIT 3"
    );

    if ($fallback) {
        while ($row = mysqli_fetch_assoc($fallback)) {
            $row["images"] = property_images_to_urls($row);
            $row["map_images"] = map_images_to_urls($row);
            $similar[] = $row;
        }
    }
}

echo json_encode([
    "status" => "success",
    "data" => [
        "property" => $property,
        "similar_properties" => $similar,
        "site_info" => $siteInfo
    ]
]);
exit;