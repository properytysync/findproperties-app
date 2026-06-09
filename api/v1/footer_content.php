<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$id = get_int("id", 1);

$stmt = $con->prepare("SELECT id, company_name, welcome_message, address, phone_number, email,
                              facebook_url, instagram_url, logo_path, linkedin_url, twitter_url
                       FROM footer_content WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) json_response(["status"=>"error","message"=>"Not found"], 404);

$row["logo_url"] = to_public_url($row["logo_path"]);

json_response(["status"=>"success","data"=>$row]);
