<?php
require_once __DIR__ . "/_helpers.php";

// ✅ Simple method guard (no require_method dependency)
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

$res = $con->query("SELECT * FROM about ORDER BY 1 ASC LIMIT 1");
$row = $res ? $res->fetch_assoc() : null;

if (!$row) {
    http_response_code(404);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["status" => "error", "message" => "About content not found"]);
    exit;
}

// Try common image column names if they exist
$imageCol = null;
foreach (["image_path","image","photo","about_image","banner_image"] as $c) {
    if (array_key_exists($c, $row)) { $imageCol = $c; break; }
}

if ($imageCol) {
    $row["image_url"] = to_public_url($row[$imageCol]);
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(["status" => "success", "data" => $row]);
exit;