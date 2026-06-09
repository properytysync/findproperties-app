<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$sid = get_int("sid", 0);
$limit = max(1, min(50, get_int("limit", 50)));

function build_state_image_url(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    $path = trim($path);
    if ($path === "") {
        return null;
    }

    $clean = str_replace("\\", "/", $path);
    $clean = ltrim($clean, "/");

    // If already absolute URL
    if (preg_match('/^https?:\/\//i', $clean)) {
        return $clean;
    }

    // Your DB stores values like: images/thumbnail4/1.jpg
    // Public path should become: /template8/images/thumbnail4/1.jpg
    if (strpos($clean, "images/") === 0) {
        return "/template8/" . $clean;
    }

    return "/" . $clean;
}

if ($sid > 0) {
    $stmt = $con->prepare("
        SELECT 
            s.sid,
            s.sname,
            s.image_path,
            COUNT(
                CASE 
                    WHEN p.pid IS NOT NULL 
                     AND LOWER(TRIM(p.status)) <> 'sold out'
                    THEN 1 
                END
            ) AS total_properties,
            COUNT(
                CASE 
                    WHEN p.pid IS NOT NULL
                     AND LOWER(TRIM(p.status)) <> 'sold out'
                     AND LOWER(TRIM(p.stype)) = 'sale'
                    THEN 1 
                END
            ) AS sale_count,
            COUNT(
                CASE 
                    WHEN p.pid IS NOT NULL
                     AND LOWER(TRIM(p.status)) <> 'sold out'
                     AND LOWER(TRIM(p.stype)) = 'rent'
                    THEN 1 
                END
            ) AS rent_count,
            COUNT(
                CASE 
                    WHEN p.pid IS NOT NULL
                     AND LOWER(TRIM(p.status)) <> 'sold out'
                     AND LOWER(TRIM(p.stype)) = 'shortlet'
                    THEN 1 
                END
            ) AS shortlet_count
        FROM state s
        LEFT JOIN property p 
            ON LOWER(TRIM(p.state)) = LOWER(TRIM(s.sname))
        WHERE s.sid = ?
        GROUP BY s.sid, s.sname, s.image_path
        LIMIT 1
    ");
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        json_response(["status" => "error", "message" => "Not found"], 404);
    }

    $row["total_properties"] = (int)($row["total_properties"] ?? 0);
    $row["sale_count"] = (int)($row["sale_count"] ?? 0);
    $row["rent_count"] = (int)($row["rent_count"] ?? 0);
    $row["shortlet_count"] = (int)($row["shortlet_count"] ?? 0);
    $row["image_url"] = build_state_image_url($row["image_path"] ?? null);

    json_response([
        "status" => "success",
        "data" => $row
    ]);
}

$stmt = $con->prepare("
    SELECT 
        s.sid,
        s.sname,
        s.image_path,
        COUNT(
            CASE 
                WHEN p.pid IS NOT NULL 
                 AND LOWER(TRIM(p.status)) <> 'sold out'
                THEN 1 
            END
        ) AS total_properties,
        COUNT(
            CASE 
                WHEN p.pid IS NOT NULL
                 AND LOWER(TRIM(p.status)) <> 'sold out'
                 AND LOWER(TRIM(p.stype)) = 'sale'
                THEN 1 
            END
        ) AS sale_count,
        COUNT(
            CASE 
                WHEN p.pid IS NOT NULL
                 AND LOWER(TRIM(p.status)) <> 'sold out'
                 AND LOWER(TRIM(p.stype)) = 'rent'
                THEN 1 
            END
        ) AS rent_count,
        COUNT(
            CASE 
                WHEN p.pid IS NOT NULL
                 AND LOWER(TRIM(p.status)) <> 'sold out'
                 AND LOWER(TRIM(p.stype)) = 'shortlet'
                THEN 1 
            END
        ) AS shortlet_count
    FROM state s
    LEFT JOIN property p 
        ON LOWER(TRIM(p.state)) = LOWER(TRIM(s.sname))
    GROUP BY s.sid, s.sname, s.image_path
    ORDER BY s.sname ASC
    LIMIT ?
");
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($r = $result->fetch_assoc()) {
    $r["total_properties"] = (int)($r["total_properties"] ?? 0);
    $r["sale_count"] = (int)($r["sale_count"] ?? 0);
    $r["rent_count"] = (int)($r["rent_count"] ?? 0);
    $r["shortlet_count"] = (int)($r["shortlet_count"] ?? 0);
    $r["image_url"] = build_state_image_url($r["image_path"] ?? null);
    $data[] = $r;
}

json_response([
    "status" => "success",
    "data" => $data
]);