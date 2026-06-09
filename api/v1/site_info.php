<?php
require_once __DIR__ . "/_helpers.php";

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $id = get_int("id", 1);

    $stmt = $con->prepare("SELECT id, logo_path, banner_image_path, currency, banner_writeup, favicon_path,
                                  welcome_message, display_mode, page_type,
                                  paystack_public_key,
                                  viewing_fee, enable_viewing_payment
                           FROM site_info WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) json_response(["status"=>"error","message"=>"Not found"], 404);

    $row["logo_url"] = to_public_url($row["logo_path"]);
    $row["banner_image_url"] = to_public_url($row["banner_image_path"]);
    $row["favicon_url"] = to_public_url($row["favicon_path"]);

    json_response(["status"=>"success","data"=>$row]);
}

// update => admin only
require_admin();

if ($method === "PUT") {
    $id = get_int("id", 1);
    $body = json_decode(file_get_contents("php://input"), true) ?: [];

    $logo_path = trim($body["logo_path"] ?? "");
    $banner_image_path = trim($body["banner_image_path"] ?? "");
    $currency = trim($body["currency"] ?? "");
    $banner_writeup = trim($body["banner_writeup"] ?? "");
    $favicon_path = trim($body["favicon_path"] ?? "");
    $welcome_message = trim($body["welcome_message"] ?? "");
    $display_mode = trim($body["display_mode"] ?? "banner");
    $page_type = trim($body["page_type"] ?? "home");
    $paystack_public_key = trim($body["paystack_public_key"] ?? "");
    $viewing_fee = isset($body["viewing_fee"]) ? (float)$body["viewing_fee"] : 1000.00;
    $enable_viewing_payment = isset($body["enable_viewing_payment"]) ? (int)$body["enable_viewing_payment"] : 0;

    if ($welcome_message === "") {
        json_response(["status"=>"error","message"=>"welcome_message is required"], 422);
    }

    // NOTE: we intentionally do NOT update paystack_secret_key here via API
    $stmt = $con->prepare("UPDATE site_info
                           SET logo_path=NULLIF(?,''), banner_image_path=NULLIF(?,''), currency=NULLIF(?,''), banner_writeup=NULLIF(?,''), favicon_path=NULLIF(?, ''),
                               welcome_message=?, display_mode=?, page_type=?,
                               paystack_public_key=NULLIF(?,''), viewing_fee=?, enable_viewing_payment=?
                           WHERE id=?");
    $stmt->bind_param(
        "sssssssssdii",
        $logo_path, $banner_image_path, $currency, $banner_writeup, $favicon_path,
        $welcome_message, $display_mode, $page_type,
        $paystack_public_key, $viewing_fee, $enable_viewing_payment, $id
    );
    $stmt->execute();

    json_response(["status"=>"success","message"=>"Updated"]);
}

json_response(["status"=>"error","message"=>"Method not allowed"], 405);
