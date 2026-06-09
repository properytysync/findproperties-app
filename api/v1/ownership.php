<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$pid = get_int("pid", 0);
$owner_id = get_int("owner_id", 0);

if ($pid <= 0 && $owner_id <= 0) {
    json_response(["status"=>"error","message"=>"Provide pid or owner_id"], 422);
}

$sql = "SELECT ownership_id, pid, owner_id FROM ownership WHERE 1=1";
$types = "";
$params = [];

if ($pid > 0) { $sql .= " AND pid=?"; $types .= "i"; $params[] = $pid; }
if ($owner_id > 0) { $sql .= " AND owner_id=?"; $types .= "i"; $params[] = $owner_id; }

$stmt = $con->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$data = [];
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $data[] = $r;

json_response(["status"=>"success","data"=>$data]);
