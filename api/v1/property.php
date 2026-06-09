<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$pid = get_int("pid", 0);

/**
 * Resolve stored image path to a public URL safely.
 * Handles:
 * - plain filename like image.jpg
 * - stored path like property/image.jpg
 * - stored path like admin/property/image.jpg
 */
function property_image_url(?string $path): ?string
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

    if (strpos($clean, "admin/property/") === 0) {
        return to_public_url($clean);
    }

    if (strpos($clean, "property/") === 0) {
        return to_public_url("admin/" . $clean);
    }

    return to_public_url("admin/property/" . $clean);
}

function property_row_format(array $r): array
{
    $r["pimage_url"]  = property_image_url($r["pimage"] ?? null);
    $r["pimage1_url"] = property_image_url($r["pimage1"] ?? null);
    $r["pimage2_url"] = property_image_url($r["pimage2"] ?? null);
    $r["pimage3_url"] = property_image_url($r["pimage3"] ?? null);
    $r["pimage4_url"] = property_image_url($r["pimage4"] ?? null);

    $r["mapimage_url"]       = property_image_url($r["mapimage"] ?? null);
    $r["topmapimage_url"]    = property_image_url($r["topmapimage"] ?? null);
    $r["groundmapimage_url"] = property_image_url($r["groundmapimage"] ?? null);

    $r["images"] = array_values(array_filter([
        $r["pimage_url"],
        $r["pimage1_url"],
        $r["pimage2_url"],
        $r["pimage3_url"],
        $r["pimage4_url"],
    ]));

    $r["map_images"] = array_values(array_filter([
        $r["mapimage_url"],
        $r["topmapimage_url"],
        $r["groundmapimage_url"]
    ]));

    return $r;
}

/**
 * SINGLE PROPERTY
 */
if ($pid > 0) {
    $stmt = $con->prepare(
        "SELECT pid, title, pcontent, type, stype, bedroom, bathroom, balcony, kitchen, toilet, size,
                price, location, city, state, feature, pimage, pimage1, pimage2, pimage3, pimage4,
                uid, status, mapimage, topmapimage, groundmapimage, totalfloor, date, is_featured, views
         FROM property
         WHERE pid = ?"
    );

    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        json_response([
            "status" => "error",
            "message" => "Property not found"
        ], 404);
    }

    $row = property_row_format($row);

    json_response([
        "status" => "success",
        "data" => $row
    ]);
}

/**
 * PROPERTY LIST + FILTERS + PAGINATION
 */
$page   = max(1, get_int("page", 1));
$limit  = max(1, min(50, get_int("limit", 12)));
$offset = ($page - 1) * $limit;

$type     = trim($_GET["type"] ?? "");
$stype    = trim($_GET["stype"] ?? "");
$state    = trim($_GET["state"] ?? "");
$city     = trim($_GET["city"] ?? "");
$status   = trim($_GET["status"] ?? "");
$featured = get_int("featured", 0);
$q        = trim($_GET["q"] ?? "");

$sql = "FROM property WHERE 1=1";
$types = "";
$params = [];

if ($type !== "") {
    $sql .= " AND type = ?";
    $types .= "s";
    $params[] = $type;
}

if ($stype !== "") {
    $sql .= " AND stype = ?";
    $types .= "s";
    $params[] = $stype;
}

if ($state !== "") {
    $sql .= " AND state = ?";
    $types .= "s";
    $params[] = $state;
}

if ($city !== "") {
    $sql .= " AND city = ?";
    $types .= "s";
    $params[] = $city;
}

if ($status !== "") {
    $sql .= " AND status = ?";
    $types .= "s";
    $params[] = $status;
}

if ($featured === 1) {
    $sql .= " AND is_featured = 1";
}

if ($q !== "") {
    $sql .= " AND (title LIKE ? OR location LIKE ? OR city LIKE ? OR state LIKE ?)";
    $types .= "ssss";
    $like = "%" . $q . "%";
    array_push($params, $like, $like, $like, $like);
}

/**
 * Total count
 */
$countSql = "SELECT COUNT(*) AS total " . $sql;
$stmt = $con->prepare($countSql);

if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()["total"] ?? 0);

/**
 * List query
 */
$listSql = "SELECT pid, title, pcontent, type, stype, bedroom, bathroom, balcony, kitchen, toilet, size,
                   price, location, city, state, feature, pimage, pimage1, pimage2, pimage3, pimage4,
                   uid, status, mapimage, topmapimage, groundmapimage, totalfloor, date, is_featured, views
            " . $sql . "
            ORDER BY date DESC, pid DESC
            LIMIT ? OFFSET ?";

$stmt = $con->prepare($listSql);

if ($types !== "") {
    $types2 = $types . "ii";
    $params2 = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($types2, ...$params2);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($r = $res->fetch_assoc()) {
    $data[] = property_row_format($r);
}

json_response([
    "status" => "success",
    "meta" => [
        "page" => $page,
        "limit" => $limit,
        "total" => $total,
        "total_pages" => (int) ceil($total / $limit),
    ],
    "data" => $data
]);