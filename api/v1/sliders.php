<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$id = get_int("id", 0);

if ($id > 0) {
    $stmt = $con->prepare("SELECT id, image_path, alt_text FROM sliders WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_response(["status"=>"error","message"=>"Not found"], 404);

    $row["image_url"] = to_public_url($row["image_path"]);
    json_response(["status"=>"success","data"=>$row]);
}

$result = $con->query("SELECT id, image_path, alt_text FROM sliders ORDER BY id DESC");
$data = [];
while ($r = $result->fetch_assoc()) {
    $r["image_url"] = to_public_url($r["image_path"]);
    $data[] = $r;
}
json_response(["status"=>"success","data"=>$data]);
