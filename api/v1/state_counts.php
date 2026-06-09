<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$limit = max(1, min(50, get_int("limit", 50)));
$sid   = max(0, get_int("sid", 0));

function state_image_url(?string $path): ?string
{
function state_image_url(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    $path = trim($path);
    if ($path === '') {
        return null;
    }

    $path = str_replace("\\", "/", $path);
    $path = ltrim($path, "/");

    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . '/' . $path;
}

function map_state_row(array $row): array
{
    $row["total_properties"] = (int)($row["total_properties"] ?? 0);
    $row["sale_count"]       = (int)($row["sale_count"] ?? 0);
    $row["rent_count"]       = (int)($row["rent_count"] ?? 0);
    $row["shortlet_count"]   = (int)($row["shortlet_count"] ?? 0);
    $row["available_count"]  = (int)($row["available_count"] ?? 0);
    $row["sold_out_count"]   = (int)($row["sold_out_count"] ?? 0);
    $row["image_url"]        = state_image_url($row["image_path"] ?? null);

    return $row;
}

$baseSql = "
    SELECT
        s.sid,
        s.sname,
        s.image_path,

        COUNT(DISTINCT p.pid) AS total_properties,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(TRIM(COALESCE(p.stype, ''))) = 'sale'
                THEN p.pid
            END
        ) AS sale_count,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(TRIM(COALESCE(p.stype, ''))) = 'rent'
                THEN p.pid
            END
        ) AS rent_count,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(TRIM(COALESCE(p.stype, ''))) = 'shortlet'
                THEN p.pid
            END
        ) AS shortlet_count,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(TRIM(COALESCE(p.status, ''))) = 'available'
                THEN p.pid
            END
        ) AS available_count,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(TRIM(COALESCE(p.status, ''))) = 'sold out'
                THEN p.pid
            END
        ) AS sold_out_count

    FROM state s
    LEFT JOIN property p
        ON LOWER(TRIM(COALESCE(p.state, ''))) = LOWER(TRIM(COALESCE(s.sname, '')))
";

if ($sid > 0) {
    $sql = $baseSql . "
        WHERE s.sid = ?
        GROUP BY s.sid, s.sname, s.image_path
        LIMIT 1
    ";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        json_response([
            "status" => "error",
            "message" => "State not found"
        ], 404);
    }

    json_response([
        "status" => "success",
        "data" => map_state_row($row)
    ]);
}

$sql = $baseSql . "
    GROUP BY s.sid, s.sname, s.image_path
    ORDER BY s.sname ASC
    LIMIT ?
";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = map_state_row($row);
}

json_response([
    "status" => "success",
    "data" => [
        "total_states" => count($items),
        "items" => $items
    ]
]);