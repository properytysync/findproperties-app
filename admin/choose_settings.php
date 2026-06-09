<?php
session_start();
include("config.php");

if (!isset($_SESSION['auser'])) {
    header("location:index.php");
    exit();
}

$msg = "";

// Ensure choose_us record exists
$secRes = mysqli_query($con, "SELECT * FROM choose_us WHERE id=1 LIMIT 1");
if (!$secRes || mysqli_num_rows($secRes) == 0) {
    mysqli_query($con, "INSERT INTO choose_us (id, title, heading, description, is_active) VALUES (1,'Why Choose Us','We Offer Perfect Real Estate Services','Update from admin',1)");
    $secRes = mysqli_query($con, "SELECT * FROM choose_us WHERE id=1 LIMIT 1");
}
$sec = mysqli_fetch_assoc($secRes);

if (isset($_POST['save_section'])) {
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $heading = mysqli_real_escape_string($con, $_POST['heading']);
    $desc = mysqli_real_escape_string($con, $_POST['description']);
    $active = isset($_POST['is_active']) ? 1 : 0;

    mysqli_query($con, "UPDATE choose_us SET title='$title', heading='$heading', description='$desc', is_active=$active WHERE id=1");
    $msg = "<p class='alert alert-success'>Section updated</p>";
    $secRes = mysqli_query($con, "SELECT * FROM choose_us WHERE id=1 LIMIT 1");
    $sec = mysqli_fetch_assoc($secRes);
}

// Save items
if (isset($_POST['save_items'])) {
    foreach ($_POST['item_id'] as $idx => $id) {
        $id = (int)$id;
        $icon = mysqli_real_escape_string($con, $_POST['icon_class'][$idx]);
        $itTitle = mysqli_real_escape_string($con, $_POST['item_title'][$idx]);
        $itContent = mysqli_real_escape_string($con, $_POST['item_content'][$idx]);
        $order = (int)$_POST['sort_order'][$idx];
        $active = isset($_POST['item_active'][$idx]) ? 1 : 0;

        mysqli_query($con, "UPDATE choose_items 
            SET icon_class='$icon', title='$itTitle', content='$itContent', sort_order=$order, is_active=$active
            WHERE id=$id AND choose_id=1");
    }
    $msg = "<p class='alert alert-success'>Items updated</p>";
}

// Fetch items
$items = [];
$itemsRes = mysqli_query($con, "SELECT * FROM choose_items WHERE choose_id=1 ORDER BY sort_order ASC");
while ($r = mysqli_fetch_assoc($itemsRes)) $items[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Why Choose Us</title>
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
	    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<div class="page-wrapper compact-wrapper" id="pageWrapper">
    <div class="page-body-wrapper">
        <div class="sidebar-wrapper" data-layout="fill-svg">
            <div><?php include('menu.php'); ?></div>
        </div>

        <div class="page-body">
            <div class="container-fluid default-dashboard">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">

                            <div class="card-header">
                                <h2 class="card-title">Homepage - Why Choose Us</h2>
                            </div>

                            <div class="card-body">
                                <?= $msg ?>

                                <h4 class="mb-3">Section Content</h4>
                                <form method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Subtitle</label>
                                        <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($sec['title']) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Heading</label>
                                        <input type="text" class="form-control" name="heading" value="<?= htmlspecialchars($sec['heading']) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($sec['description']) ?></textarea>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= ((int)$sec['is_active'] === 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">Show this section on homepage</label>
                                    </div>

                                    <button class="btn btn-primary" type="submit" name="save_section">Save Section</button>
                                </form>

                                <hr class="my-5">

                                <h4 class="mb-3">4 Boxes</h4>
                                <form method="post">
                                    <?php foreach ($items as $i => $it): ?>
                                        <div class="border rounded p-3 mb-3">
                                            <input type="hidden" name="item_id[]" value="<?= (int)$it['id'] ?>">

                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <label class="form-label">Icon Class</label>
                                                    <input type="text" class="form-control" name="icon_class[]" value="<?= htmlspecialchars($it['icon_class']) ?>">
                                                    <small>Example: flaticon-location</small>
                                                </div>

                                                <div class="col-md-5 mb-2">
                                                    <label class="form-label">Title</label>
                                                    <input type="text" class="form-control" name="item_title[]" value="<?= htmlspecialchars($it['title']) ?>">
                                                </div>

                                                <div class="col-md-3 mb-2">
                                                    <label class="form-label">Order</label>
                                                    <input type="number" class="form-control" name="sort_order[]" value="<?= (int)$it['sort_order'] ?>">
                                                </div>
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label">Text</label>
                                                <textarea class="form-control" name="item_content[]" rows="2"><?= htmlspecialchars($it['content']) ?></textarea>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="item_active[<?= $i ?>]" id="item_active_<?= $i ?>" <?= ((int)$it['is_active'] === 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="item_active_<?= $i ?>">Active</label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <button class="btn btn-primary" type="submit" name="save_items">Save Boxes</button>
                                </form>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
