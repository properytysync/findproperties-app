<?php
require_once __DIR__ . "/_helpers.php";

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "POST") {
    $body = json_decode(file_get_contents("php://input"), true) ?: [];
    $email = trim($body["email"] ?? "");

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(["status"=>"error","message"=>"Valid email is required"], 422);
    }

    // prevent duplicates (email is indexed but not unique, so handle gracefully)
    $stmt = $con->prepare("SELECT id FROM newsletter_subscribers WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();

    if ($exists) {
        json_response(["status"=>"success","message"=>"Already subscribed"]);
    }

    $stmt = $con->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    json_response(["status"=>"success","message"=>"Subscribed","id"=>$con->insert_id], 201);
}

require_admin();
require_method("GET");

$result = $con->query("SELECT id, email, created_at FROM newsletter_subscribers ORDER BY id DESC");
$data = [];
while ($r = $result->fetch_assoc()) $data[] = $r;

json_response(["status"=>"success","data"=>$data]);
