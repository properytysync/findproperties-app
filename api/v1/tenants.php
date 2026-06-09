<?php
require_once __DIR__ . "/_helpers.php";

require_admin();

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $tenant_id = get_int("tenant_id", 0);
    $pid = get_int("pid", 0);

    if ($tenant_id > 0) {
        $stmt = $con->prepare("SELECT tenant_id, pid, name, contact_info, tenant_type, lease_start, lease_end, purchase_date, amount_paid
                               FROM tenants WHERE tenant_id=?");
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) json_response(["status"=>"error","message"=>"Not found"], 404);
        json_response(["status"=>"success","data"=>$row]);
    }

    $sql = "SELECT tenant_id, pid, name, contact_info, tenant_type, lease_start, lease_end, purchase_date, amount_paid
            FROM tenants WHERE 1=1";
    $types = "";
    $params = [];

    if ($pid > 0) { $sql .= " AND pid=?"; $types .= "i"; $params[] = $pid; }

    $sql .= " ORDER BY tenant_id DESC";

    $stmt = $con->prepare($sql);
    if ($types !== "") $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $data = [];
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $data[] = $r;

    json_response(["status"=>"success","data"=>$data]);
}

if ($method === "POST") {
    $body = json_decode(file_get_contents("php://input"), true) ?: [];

    $pid = isset($body["pid"]) ? (int)$body["pid"] : null;
    $name = trim($body["name"] ?? "");
    $contact_info = trim($body["contact_info"] ?? "");
    $tenant_type = trim($body["tenant_type"] ?? "");
    $lease_start = trim($body["lease_start"] ?? "");
    $lease_end = trim($body["lease_end"] ?? "");
    $purchase_date = trim($body["purchase_date"] ?? "");
    $amount_paid = isset($body["amount_paid"]) ? (float)$body["amount_paid"] : 0.00;

    if ($name === "" || $contact_info === "" || !in_array($tenant_type, ["renter","buyer"], true)) {
        json_response(["status"=>"error","message"=>"name, contact_info, tenant_type (renter/buyer) are required"], 422);
    }

    // If renter => lease dates should be allowed. If buyer => purchase_date allowed.
    $stmt = $con->prepare("INSERT INTO tenants (pid, name, contact_info, tenant_type, lease_start, lease_end, purchase_date, amount_paid)
                           VALUES (?, ?, ?, ?, NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), ?)");
    // pid can be null
    $pidParam = $pid;
    $stmt->bind_param("issssssd", $pidParam, $name, $contact_info, $tenant_type, $lease_start, $lease_end, $purchase_date, $amount_paid);
    $stmt->execute();

    json_response(["status"=>"success","message"=>"Created","tenant_id"=>$con->insert_id], 201);
}

if ($method === "PUT") {
    $tenant_id = get_int("tenant_id", 0);
    if ($tenant_id <= 0) json_response(["status"=>"error","message"=>"tenant_id is required"], 422);

    $body = json_decode(file_get_contents("php://input"), true) ?: [];

    $pid = isset($body["pid"]) ? (int)$body["pid"] : null;
    $name = trim($body["name"] ?? "");
    $contact_info = trim($body["contact_info"] ?? "");
    $tenant_type = trim($body["tenant_type"] ?? "");
    $lease_start = trim($body["lease_start"] ?? "");
    $lease_end = trim($body["lease_end"] ?? "");
    $purchase_date = trim($body["purchase_date"] ?? "");
    $amount_paid = isset($body["amount_paid"]) ? (float)$body["amount_paid"] : 0.00;

    if ($name === "" || $contact_info === "" || !in_array($tenant_type, ["renter","buyer"], true)) {
        json_response(["status"=>"error","message"=>"name, contact_info, tenant_type (renter/buyer) are required"], 422);
    }

    $stmt = $con->prepare("UPDATE tenants
                           SET pid=?, name=?, contact_info=?, tenant_type=?, lease_start=NULLIF(?,''), lease_end=NULLIF(?,''), purchase_date=NULLIF(?,''), amount_paid=?
                           WHERE tenant_id=?");
    $pidParam = $pid;
    $stmt->bind_param("issssssdi", $pidParam, $name, $contact_info, $tenant_type, $lease_start, $lease_end, $purchase_date, $amount_paid, $tenant_id);
    $stmt->execute();

    json_response(["status"=>"success","message"=>"Updated"]);
}

if ($method === "DELETE") {
    $tenant_id = get_int("tenant_id", 0);
    if ($tenant_id <= 0) json_response(["status"=>"error","message"=>"tenant_id is required"], 422);

    $stmt = $con->prepare("DELETE FROM tenants WHERE tenant_id=?");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();

    json_response(["status"=>"success","message"=>"Deleted"]);
}

json_response(["status"=>"error","message"=>"Method not allowed"], 405);
