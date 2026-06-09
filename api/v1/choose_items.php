<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$id = get_int("id", 0);
$choose_id = get_int("choose_id", 0);

if ($id > 0) {
    $stmt = $con->prepare("SELECT id, choose_id, icon_class, title, content, sort_order, is_active
                           FROM choose_items WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_response(["status"=>"error","message"=>"Not found"], 404);
    json_response(["status"=>"success","data"=>$row]);
}

if ($choose_id <= 0) {
    json_response(["status"=>"error","message"=>"choose_id is required"], 422);
}

$stmt = $con->prepare("SELECT id, choose_id, icon_class, title, content, sort_order, is_active
                       FROM choose_items
                       WHERE choose_id=? AND is_active=1
                       ORDER BY sort_order ASC, id ASC");
$stmt->bind_param("i", $choose_id);
$stmt->execute();

$data = [];
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $data[] = $r;

json_response(["status"=>"success","data"=>$data]);
