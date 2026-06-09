<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$cid = get_int("cid", 0);
$sid = get_int("sid", 0);

if ($cid > 0) {
    $stmt = $con->prepare("SELECT cid, cname, sid FROM city WHERE cid=?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_response(["status"=>"error","message"=>"Not found"], 404);
    json_response(["status"=>"success","data"=>$row]);
}

if ($sid <= 0) {
    json_response(["status"=>"error","message"=>"sid (state id) is required"], 422);
}

$stmt = $con->prepare("SELECT cid, cname, sid FROM city WHERE sid=? ORDER BY cname ASC");
$stmt->bind_param("i", $sid);
$stmt->execute();

$data = [];
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $data[] = $r;

json_response(["status"=>"success","data"=>$data]);
