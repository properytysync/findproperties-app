<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$id = get_int("id", 0);

if ($id > 0) {
    $stmt = $con->prepare("SELECT id, title, heading, description, is_active, updated_at FROM choose_us WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_response(["status"=>"error","message"=>"Not found"], 404);
    json_response(["status"=>"success","data"=>$row]);
}

$result = $con->query("SELECT id, title, heading, description, is_active, updated_at
                       FROM choose_us WHERE is_active=1 ORDER BY id DESC");
$data = [];
while ($r = $result->fetch_assoc()) $data[] = $r;

json_response(["status"=>"success","data"=>$data]);
