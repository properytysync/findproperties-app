<?php
require_once __DIR__ . "/_helpers.php";

header("Content-Type: application/json; charset=utf-8");

$sql = "SELECT * FROM about ORDER BY id DESC LIMIT 1";
$result = mysqli_query($con, $sql);

if (!$result) {
    echo json_encode([
        "status" => "error",
        "message" => "Database query failed: " . mysqli_error($con)
    ]);
    exit;
}

$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo json_encode([
        "status" => "error",
        "message" => "No about content found"
    ]);
    exit;
}

$row["image_url"] = !empty($row["image"])
    ? to_public_url("admin/upload/" . ltrim($row["image"], "/"))
    : null;

$row["image2_url"] = !empty($row["image2"])
    ? to_public_url("admin/upload/" . ltrim($row["image2"], "/"))
    : null;

echo json_encode([
    "status" => "success",
    "data" => $row
]);
exit;