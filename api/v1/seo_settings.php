<?php
require_once __DIR__ . "/_helpers.php";

header("Content-Type: application/json; charset=utf-8");

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method === "GET") {
    $id = get_int("id", 0);
    $page = trim($_GET["page"] ?? "");

    if ($id > 0) {
        $stmt = $con->prepare("
            SELECT id, page, seo_title, seo_description, seo_keywords, seo_author
            FROM seo_settings
            WHERE id = ?
            LIMIT 1
        ");
        if (!$stmt) {
            json_response(["status" => "error", "message" => "Prepare failed"], 500);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            json_response(["status" => "error", "message" => "Not found"], 404);
        }

        json_response(["status" => "success", "data" => $row]);
    }

    if ($page !== "") {
        $stmt = $con->prepare("
            SELECT id, page, seo_title, seo_description, seo_keywords, seo_author
            FROM seo_settings
            WHERE page = ?
            LIMIT 1
        ");
        if (!$stmt) {
            json_response(["status" => "error", "message" => "Prepare failed"], 500);
        }

        $stmt->bind_param("s", $page);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            json_response(["status" => "error", "message" => "SEO settings not found for page"], 404);
        }

        json_response(["status" => "success", "data" => $row]);
    }

    $result = $con->query("
        SELECT id, page, seo_title, seo_description, seo_keywords, seo_author
        FROM seo_settings
        ORDER BY id DESC
    ");

    if (!$result) {
        json_response(["status" => "error", "message" => "Query failed"], 500);
    }

    $data = [];
    while ($r = $result->fetch_assoc()) {
        $data[] = $r;
    }

    json_response(["status" => "success", "data" => $data]);
}

// write endpoints => admin only
require_admin();

if ($method === "POST") {
    $body = json_decode(file_get_contents("php://input"), true) ?: [];

    $page = trim($body["page"] ?? "");
    $seo_title = trim($body["seo_title"] ?? "");
    $seo_description = trim($body["seo_description"] ?? "");
    $seo_keywords = trim($body["seo_keywords"] ?? "");
    $seo_author = trim($body["seo_author"] ?? "");

    if ($page === "" || $seo_title === "" || $seo_description === "") {
        json_response(["status" => "error", "message" => "page, seo_title, seo_description are required"], 422);
    }

    $stmt = $con->prepare("
        INSERT INTO seo_settings (id, page, seo_title, seo_description, seo_keywords, seo_author)
        VALUES (NULL, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))
    ");
    if (!$stmt) {
        json_response(["status" => "error", "message" => "Prepare failed"], 500);
    }

    $stmt->bind_param("sssss", $page, $seo_title, $seo_description, $seo_keywords, $seo_author);
    $stmt->execute();
    $insertId = $con->insert_id;
    $stmt->close();

    json_response(["status" => "success", "message" => "Created", "id" => $insertId], 201);
}

if ($method === "PUT") {
    $id = get_int("id", 0);
    if ($id <= 0) {
        json_response(["status" => "error", "message" => "id is required"], 422);
    }

    $body = json_decode(file_get_contents("php://input"), true) ?: [];

    $page = trim($body["page"] ?? "");
    $seo_title = trim($body["seo_title"] ?? "");
    $seo_description = trim($body["seo_description"] ?? "");
    $seo_keywords = trim($body["seo_keywords"] ?? "");
    $seo_author = trim($body["seo_author"] ?? "");

    if ($page === "" || $seo_title === "" || $seo_description === "") {
        json_response(["status" => "error", "message" => "page, seo_title, seo_description are required"], 422);
    }

    $stmt = $con->prepare("
        UPDATE seo_settings
        SET
            page = ?,
            seo_title = ?,
            seo_description = ?,
            seo_keywords = NULLIF(?, ''),
            seo_author = NULLIF(?, '')
        WHERE id = ?
    ");
    if (!$stmt) {
        json_response(["status" => "error", "message" => "Prepare failed"], 500);
    }

    $stmt->bind_param("sssssi", $page, $seo_title, $seo_description, $seo_keywords, $seo_author, $id);
    $stmt->execute();
    $stmt->close();

    json_response(["status" => "success", "message" => "Updated"]);
}

if ($method === "DELETE") {
    $id = get_int("id", 0);
    if ($id <= 0) {
        json_response(["status" => "error", "message" => "id is required"], 422);
    }

    $stmt = $con->prepare("DELETE FROM seo_settings WHERE id = ?");
    if (!$stmt) {
        json_response(["status" => "error", "message" => "Prepare failed"], 500);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    json_response(["status" => "success", "message" => "Deleted"]);
}

json_response(["status" => "error", "message" => "Method not allowed"], 405);