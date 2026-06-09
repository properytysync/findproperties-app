<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$limit = get_int("limit", 12);
if ($limit <= 0) $limit = 12;

$countRes = $con->query("SELECT COUNT(*) AS total_agents FROM agents");
$totalAgents = 0;
if ($countRes) {
  $row = $countRes->fetch_assoc();
  $totalAgents = (int)($row["total_agents"] ?? 0);
}

$stmt = $con->prepare("SELECT agent_id, name, description, picture FROM agents ORDER BY agent_id DESC LIMIT ?");
$stmt->bind_param("i", $limit);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($r = $res->fetch_assoc()) {
  $r["picture_url"] = to_public_url($r["picture"] ?? null);
  $data[] = $r;
}

json_response([
  "status" => "success",
  "data" => [
    "total" => $totalAgents,
    "agents" => $data
  ]
]);