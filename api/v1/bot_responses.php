<?php
require_once __DIR__ . "/_helpers.php";

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $id = get_int("id", 0);
    $category = trim($_GET["category"] ?? "");
    $pattern = trim($_GET["pattern"] ?? "");

    if ($id > 0) {
        $stmt = $con->prepare("SELECT id, pattern, response, category, created_at, priority, next_context
                               FROM bot_responses WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) json_response(["status"=>"error","message"=>"Not found"], 404);
        json_response(["status"=>"success","data"=>$row]);
    }

    // filters
    $sql = "SELECT id, pattern, response, category, created_at, priority, next_context
            FROM bot_responses WHERE 1=1";
    $types = "";
    $params = [];

    if ($category !== "") {
        $sql .= " AND category = ?";
        $types .= "s";
        $params[] = $category;
    }
    if ($pattern !== "") {
        $sql .= " AND pattern LIKE ?";
        $types .= "s";
        $params[] = "%" . $pattern . "%";
    }

    $sql .= " ORDER BY priority DESC, id DESC";

    $stmt = $con->prepare($sql);
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($r = $res->fetch_assoc()) $data[] = $r;

    json_response(["status"=>"success","data"=>$data]);
}

/**
 * Write endpoints (admin only)
 */
require_admin();

if ($method === "POST") {
    $body = json_decode(file_get_contents("php://input"), true) ?: [];
    $pattern = trim($body["pattern"] ?? "");
    $response = trim($body["response"] ?? "");
    $category = trim($body["category"] ?? "");
    $priority = isset($body["priority"]) ? (int)$body["priority"] : 1;
    $next_context = trim($body["next_context"] ?? "");

    if ($pattern === "" || $response === "") {
        json_response(["status"=>"error","message"=>"pattern and response are required"], 422);
    }

    $stmt = $con->prepare("INSERT INTO bot_responses (pattern, response, category, priority, next_context)
                           VALUES (?, ?, NULLIF(?,''), ?, NULLIF(?,''))");
    $stmt->bind_param("sssis", $pattern, $response, $category, $priority, $next_context);
    $stmt->execute();

    json_response(["status"=>"success","message"=>"Created","id"=>$con->insert_id], 201);
}

if ($method === "PUT") {
    $id = get_int("id", 0);
    if ($id <= 0) json_response(["status"=>"error","message"=>"id is required"], 422);

    $body = json_decode(file_get_contents("php://input"), true) ?: [];
    $pattern = trim($body["pattern"] ?? "");
    $response = trim($body["response"] ?? "");
    $category = trim($body["category"] ?? "");
    $priority = isset($body["priority"]) ? (int)$body["priority"] : 1;
    $next_context = trim($body["next_context"] ?? "");

    if ($pattern === "" || $response === "") {
        json_response(["status"=>"error","message"=>"pattern and response are required"], 422);
    }

    $stmt = $con->prepare("UPDATE bot_responses
                           SET pattern=?, response=?, category=NULLIF(?,''), priority=?, next_context=NULLIF(?, '')
                           WHERE id=?");
    $stmt->bind_param("sssisi", $pattern, $response, $category, $priority, $next_context, $id);
    $stmt->execute();

    json_response(["status"=>"success","message"=>"Updated"]);
}

if ($method === "DELETE") {
    $id = get_int("id", 0);
    if ($id <= 0) json_response(["status"=>"error","message"=>"id is required"], 422);

    $stmt = $con->prepare("DELETE FROM bot_responses WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    json_response(["status"=>"success","message"=>"Deleted"]);
}

json_response(["status"=>"error","message"=>"Method not allowed"], 405);
