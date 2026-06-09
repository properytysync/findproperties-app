<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$page_type = get_str("page_type", "home");

$stmt = $con->prepare("
  SELECT id, page_type, tab_label, tab_stype, sort_order, is_active
  FROM banner_tabs
  WHERE page_type=? AND is_active=1
  ORDER BY sort_order ASC
");
$stmt->bind_param("s", $page_type);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($r = $res->fetch_assoc()) $data[] = $r;

json_response(["status" => "success", "data" => $data]);