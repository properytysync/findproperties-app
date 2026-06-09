<?php
// template6/admin/banner_slider.php
// FULL working page: toggle slider/hero + manage hero + manage slides

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("config.php");

// Redirect if not logged in (your system uses auser)
if (!isset($_SESSION['auser'])) {
    header("location:index.php");
    exit();
}

if (!$con) {
    die("DB connection failed: " . mysqli_connect_error());
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ---------- Ensure DB tables exist ----------
$createSlides = "
CREATE TABLE IF NOT EXISTS banner_slides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  page_type VARCHAR(50) DEFAULT 'home',
  span_text VARCHAR(255) DEFAULT NULL,
  heading_text VARCHAR(255) DEFAULT NULL,
  background_image_path VARCHAR(255) DEFAULT NULL,
  button_text VARCHAR(100) DEFAULT 'Search',
  button_link VARCHAR(255) DEFAULT '#',
  is_active TINYINT(1) DEFAULT 1,
  sort_order INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
mysqli_query($con, $createSlides);

// ---------- Helpers ----------
function uploadImage($inputName, $targetDirRelativeFromAdmin = "../images/") {
    if (!isset($_FILES[$inputName]) || empty($_FILES[$inputName]['name'])) {
        return "";
    }

    if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return "";
    }

    $targetDirectory = $targetDirRelativeFromAdmin;
    if (!is_dir($targetDirectory)) {
        // try create
        @mkdir($targetDirectory, 0775, true);
    }

    $originalName = basename($_FILES[$inputName]["name"]);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // basic whitelist
    $allowed = ["jpg","jpeg","png","webp","gif"];
    if (!in_array($ext, $allowed)) {
        die("Invalid file type. Allowed: jpg, jpeg, png, webp, gif");
    }

    // safer unique filename
    $safeName = time() . "_" . preg_replace("/[^a-zA-Z0-9_\-\.]/", "_", $originalName);
    $targetFile = rtrim($targetDirectory, "/") . "/" . $safeName;

    if (move_uploaded_file($_FILES[$inputName]["tmp_name"], $targetFile)) {
        // DB path format like your current code: "images/filename.ext"
        return "images/" . $safeName;
    }

    return "";
}

// ---------- Load current site settings (site_info id=1) ----------
$siteRow = [
    "display_mode" => "banner",
    "banner_writeup" => "",
    "banner_image_path" => "",
];

$siteRes = mysqli_query($con, "SELECT display_mode, banner_writeup, banner_image_path FROM site_info WHERE id=1 LIMIT 1");
if ($siteRes && mysqli_num_rows($siteRes) > 0) {
    $siteRow = mysqli_fetch_assoc($siteRes);
}

// ---------- Actions ----------
$success = "";
$error = "";

// 1) Save display mode + hero fields
if (isset($_POST["save_mode"])) {
    $display_mode = $_POST["display_mode"] ?? "banner";
    if (!in_array($display_mode, ["banner", "hero"])) $display_mode = "banner";

    $hero_writeup = $_POST["banner_writeup"] ?? "";
    $hero_writeup = trim($hero_writeup);

    // Optional hero image upload (banner_image_path)
    $heroImagePath = uploadImage("hero_image"); // returns images/...
    if ($heroImagePath === "") {
        // if no new upload, keep existing
        $heroImagePath = $siteRow["banner_image_path"] ?? "";
    }

    // Update DB
    $stmt = $con->prepare("UPDATE site_info SET display_mode=?, banner_writeup=?, banner_image_path=? WHERE id=1");
    if (!$stmt) {
        $error = "Prepare failed: " . $con->error;
    } else {
        $stmt->bind_param("sss", $display_mode, $hero_writeup, $heroImagePath);
        if ($stmt->execute()) {
            $success = "Saved display mode and hero settings.";
        } else {
            $error = "Save failed: " . $stmt->error;
        }
        $stmt->close();
    }

    // reload
    $siteRes = mysqli_query($con, "SELECT display_mode, banner_writeup, banner_image_path FROM site_info WHERE id=1 LIMIT 1");
    if ($siteRes && mysqli_num_rows($siteRes) > 0) $siteRow = mysqli_fetch_assoc($siteRes);
}

// 2) Add new slide
if (isset($_POST["add_slide"])) {
    $span_text = trim($_POST["span_text"] ?? "");
    $heading_text = trim($_POST["heading_text"] ?? "");
    $button_text = trim($_POST["button_text"] ?? "Search");
    $button_link = trim($_POST["button_link"] ?? "property.php");
    $sort_order = (int)($_POST["sort_order"] ?? 1);
    $is_active = isset($_POST["is_active"]) ? 1 : 0;

    $bgPath = uploadImage("bg_image"); // required for slide ideally
    if ($bgPath === "") {
        $error = "Slide image is required (or upload failed).";
    } else {
        $stmt = $con->prepare("
            INSERT INTO banner_slides
            (page_type, span_text, heading_text, background_image_path, button_text, button_link, is_active, sort_order)
            VALUES ('home', ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            $error = "Prepare failed: " . $con->error;
        } else {
            $stmt->bind_param("sssssii", $span_text, $heading_text, $bgPath, $button_text, $button_link, $is_active, $sort_order);
            if ($stmt->execute()) {
                $success = "Slide added successfully.";
            } else {
                $error = "Insert failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// 3) Toggle slide active
if (isset($_GET["toggle"]) && isset($_GET["id"])) {
    $id = (int)$_GET["id"];
    $toggleRes = mysqli_query($con, "SELECT is_active FROM banner_slides WHERE id=$id LIMIT 1");
    if ($toggleRes && mysqli_num_rows($toggleRes) > 0) {
        $r = mysqli_fetch_assoc($toggleRes);
        $new = ((int)$r["is_active"] === 1) ? 0 : 1;
        mysqli_query($con, "UPDATE banner_slides SET is_active=$new WHERE id=$id");
        header("Location: banner_slider.php?ok=1");
        exit();
    }
}

// 4) Delete slide
if (isset($_GET["delete"]) && isset($_GET["id"])) {
    $id = (int)$_GET["id"];
    mysqli_query($con, "DELETE FROM banner_slides WHERE id=$id");
    header("Location: banner_slider.php?deleted=1");
    exit();
}

// ---------- Load slides ----------
$slides = [];
$slideRes = mysqli_query($con, "SELECT * FROM banner_slides WHERE page_type='home' ORDER BY sort_order ASC, id DESC");
if ($slideRes) {
    while ($s = mysqli_fetch_assoc($slideRes)) $slides[] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banner Settings - Slider / Hero</title>

    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">
    <style>
        body { background:#f6f7fb; }
        .wrap { max-width: 1100px; margin: 30px auto; padding: 0 15px; }
        .cardx { background:#fff; border-radius:12px; padding:20px; box-shadow:0 8px 25px rgba(0,0,0,.06); margin-bottom:18px;}
        .row-gap { row-gap: 12px; }
        .badge-on { background:#d1fae5; color:#065f46; padding:4px 10px; border-radius:999px; font-size:12px; }
        .badge-off { background:#fee2e2; color:#991b1b; padding:4px 10px; border-radius:999px; font-size:12px; }
        .img-prev { width:110px; height:70px; object-fit:cover; border-radius:8px; border:1px solid #eee; }
        .small { font-size: 13px; color:#666; }
    </style>
</head>
<body>

<div class="wrap">

    <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Homepage Banner Settings</h3>
    <a href="dashboard.php" class="btn btn-outline-secondary">
        ← Return to Dashboard
    </a>
</div>

    <p class="small mb-4">
        Choose what appears on the homepage: <b>Slider</b> (multiple slides) or <b>Hero</b> (single write-up).
    </p>

    <?php if (!empty($success) || isset($_GET["ok"]) || isset($_GET["deleted"])): ?>
        <div class="alert alert-success">
            <?= e($success ?: (isset($_GET["deleted"]) ? "Slide deleted." : "Updated.")) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- MODE + HERO SETTINGS -->
    <div class="cardx">
        <form method="post" enctype="multipart/form-data">
            <h5 class="mb-3">1) Display Mode</h5>

            <div class="d-flex gap-4 align-items-center mb-3">
                <label class="d-flex align-items-center gap-2">
                    <input type="radio" name="display_mode" value="banner" <?= ($siteRow["display_mode"] === "banner") ? "checked" : "" ?>>
                    <span><b>Slider</b> (Banner)</span>
                </label>

                <label class="d-flex align-items-center gap-2">
                    <input type="radio" name="display_mode" value="hero" <?= ($siteRow["display_mode"] === "hero") ? "checked" : "" ?>>
                    <span><b>Hero</b> (Single headline)</span>
                </label>
            </div>

            <hr>

            <h5 class="mb-3">2) Hero Settings (used when Hero is selected)</h5>

            <div class="row row-gap">
                <div class="col-md-8">
                   
                    <textarea class="form-control" name="banner_writeup" rows="3"
                        placeholder="Example: Home Is The Starting Place Of Love, Hope And Dreams"><?= e($siteRow["banner_writeup"]) ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Hero Image (optional) (uses <code>site_info.banner_image_path</code>)</label>
                    <input type="file" class="form-control" name="hero_image">
                    <?php if (!empty($siteRow["banner_image_path"])): ?>
                        <div class="mt-2">
                            <div class="small mb-1">Current:</div>
                            <img class="img-prev" src="../<?= e($siteRow["banner_image_path"]) ?>" alt="Hero Image">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-primary" type="submit" name="save_mode">Save Settings</button>
            </div>
        </form>
    </div>

    <!-- SLIDER MANAGEMENT -->
    <div class="cardx">
        <h5 class="mb-3">3) Slider Settings (used when Slider is selected)</h5>
        <p class="small">
            Add slides here. Homepage will show all <b>Active</b> slides ordered by <b>Sort Order</b>.
        </p>

        <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="row row-gap">
                <div class="col-md-6">
                    <label class="form-label">Small text (Span)</label>
                    <input type="text" class="form-control" name="span_text" placeholder="Find A Modern, Safe & Secure Home">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Heading (H1)</label>
                    <input type="text" class="form-control" name="heading_text" placeholder="Select Your Comfort Home From Our New Collection">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Slide Image (Required)</label>
                    <input type="file" class="form-control" name="bg_image" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Button Text</label>
                    <input type="text" class="form-control" name="button_text" value="Search">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Button Link</label>
                    <input type="text" class="form-control" name="button_link" value="property.php">
                </div>

                <div class="col-md-1">
                    <label class="form-label">Order</label>
                    <input type="number" class="form-control" name="sort_order" value="1">
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <label class="d-flex gap-2 align-items-center mb-0">
                        <input type="checkbox" name="is_active" checked>
                        <span class="small">Active</span>
                    </label>
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-success" type="submit" name="add_slide">Add Slide</button>
            </div>
        </form>

        <h6 class="mb-2">Existing Slides</h6>

        <?php if (empty($slides)): ?>
            <div class="alert alert-warning">No slides yet. Add your first slide above.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th style="width:120px;">Image</th>
                            <th>Span</th>
                            <th>Heading</th>
                            <th style="width:110px;">Order</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($slides as $s): ?>
                        <tr>
                            <td>
                                <?php if (!empty($s["background_image_path"])): ?>
                                    <img class="img-prev" src="../<?= e($s["background_image_path"]) ?>" alt="Slide">
                                <?php endif; ?>
                            </td>
                            <td><?= e($s["span_text"]) ?></td>
                            <td><?= e($s["heading_text"]) ?></td>
                            <td><?= (int)$s["sort_order"] ?></td>
                            <td>
                                <?php if ((int)$s["is_active"] === 1): ?>
                                    <span class="badge-on">Active</span>
                                <?php else: ?>
                                    <span class="badge-off">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="banner_slider.php?toggle=1&id=<?= (int)$s["id"] ?>">
                                    <?= ((int)$s["is_active"] === 1) ? "Deactivate" : "Activate" ?>
                                </a>

                                <a class="btn btn-sm btn-outline-danger"
                                   href="banner_slider.php?delete=1&id=<?= (int)$s["id"] ?>"
                                   onclick="return confirm('Delete this slide?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

       
    </div>

   

</div>

</body>
</html>
