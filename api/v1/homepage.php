<?php
require_once 'config.php';

header('Content-Type: application/json');

$cacheFile = __DIR__ . '/cache_homepage.json';
$cacheTime = 120;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    echo file_get_contents($cacheFile);
    exit;
}

$data = [];

/* SITE INFO */
$site = mysqli_query($con, "
    SELECT *
    FROM site_info
    ORDER BY id DESC
    LIMIT 1
");
$data['site_info'] = $site ? mysqli_fetch_assoc($site) : null;

/* SEO SETTINGS */
$seo = mysqli_query($con, "
    SELECT *
    FROM seo_settings
    WHERE page_name = 'homepage'
    LIMIT 1
");
$data['seo'] = $seo ? mysqli_fetch_assoc($seo) : null;

/* FEATURED PROPERTIES */
$featured = mysqli_query($con, "
    SELECT *
    FROM property
    WHERE is_featured = 1
    ORDER BY date DESC
    LIMIT 6
");

$data['featured_properties'] = [];
if ($featured) {
    while ($row = mysqli_fetch_assoc($featured)) {
        $data['featured_properties'][] = $row;
    }
}

/* LATEST PROPERTIES */
$latest = mysqli_query($con, "
    SELECT *
    FROM property
    ORDER BY date DESC
    LIMIT 6
");

$data['latest_properties'] = [];
if ($latest) {
    while ($row = mysqli_fetch_assoc($latest)) {
        $data['latest_properties'][] = $row;
    }
}

/* STATE COUNTS */
$states = mysqli_query($con, "
    SELECT
        s.id AS sid,
        s.name AS sname,
        s.image_path,
        COUNT(p.pid) AS total_properties,
        SUM(CASE WHEN LOWER(TRIM(p.stype)) = 'sale' THEN 1 ELSE 0 END) AS sale_count,
        SUM(CASE WHEN LOWER(TRIM(p.stype)) = 'rent' THEN 1 ELSE 0 END) AS rent_count,
        SUM(CASE WHEN LOWER(TRIM(p.stype)) = 'shortlet' THEN 1 ELSE 0 END) AS shortlet_count
    FROM state s
    LEFT JOIN property p ON p.sid = s.id
    GROUP BY s.id, s.name, s.image_path
    ORDER BY total_properties DESC
    LIMIT 20
");

$data['state_counts'] = [];
if ($states) {
    while ($row = mysqli_fetch_assoc($states)) {
        if (!empty($row['image_path'])) {
            $row['image_url'] = $row['image_path'];
        } else {
            $row['image_url'] = null;
        }
        $data['state_counts'][] = $row;
    }
}

$json = json_encode($data);
file_put_contents($cacheFile, $json);

echo $json;