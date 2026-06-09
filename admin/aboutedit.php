<?php
session_start();
include("config.php");

if (!isset($_SESSION['auser'])) {
    header("location:index.php");
    exit();
}

$msg = "";

// Fetch latest about row (or create one)
$aboutRes = mysqli_query($con, "SELECT * FROM about ORDER BY id DESC LIMIT 1");
if (!$aboutRes || mysqli_num_rows($aboutRes) == 0) {
    mysqli_query($con, "INSERT INTO about (title, content, image) VALUES ('About Us', '', '')");
    $aboutRes = mysqli_query($con, "SELECT * FROM about ORDER BY id DESC LIMIT 1");
}
$about = mysqli_fetch_assoc($aboutRes);
$aid = (int)$about['id'];

if (isset($_POST['update'])) {

    $title = mysqli_real_escape_string($con, $_POST['utitle']);
    $subtitle = mysqli_real_escape_string($con, $_POST['usubtitle']);
    $content = mysqli_real_escape_string($con, $_POST['ucontent']);
    $years = (int)$_POST['uyears'];
    $video = mysqli_real_escape_string($con, $_POST['uvideo']); // store youtube ID

    $newImage1 = $about['image'] ?? '';
    $newImage2 = $about['image2'] ?? '';

    // Upload main image
    if (!empty($_FILES['aimage']['name'])) {
        $aimage = time() . "_" . basename($_FILES['aimage']['name']);
        $tmp = $_FILES['aimage']['tmp_name'];
        if (move_uploaded_file($tmp, "upload/$aimage")) {
            $newImage1 = $aimage;
        }
    }

    // Upload secondary image
    if (!empty($_FILES['aimage2']['name'])) {
        $aimage2 = time() . "_2_" . basename($_FILES['aimage2']['name']);
        $tmp2 = $_FILES['aimage2']['tmp_name'];
        if (move_uploaded_file($tmp2, "upload/$aimage2")) {
            $newImage2 = $aimage2;
        }
    }

    $sql = "UPDATE about SET 
                title='$title',
                subtitle='$subtitle',
                content='$content',
                years_experience='$years',
                video_url='$video',
                image='$newImage1',
                image2='$newImage2'
            WHERE id=$aid";

    $result = mysqli_query($con, $sql);

    if ($result) {
        $msg = "<p class='alert alert-success'>About Updated Successfully</p>";
    } else {
        $msg = "<p class='alert alert-danger'>Update Failed: " . mysqli_error($con) . "</p>";
    }

    // Refresh about data
    $aboutRes = mysqli_query($con, "SELECT * FROM about WHERE id=$aid LIMIT 1");
    $about = mysqli_fetch_assoc($aboutRes);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - About Settings</title>
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
                                <h2 class="card-title">About Page Settings</h2>
                            </div>

                            <div class="card-body">
                                <?= $msg ?>

                                <form method="post" enctype="multipart/form-data">

                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="utitle" value="<?= htmlspecialchars($about['title'] ?? '') ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Subtitle</label>
                                        <input type="text" class="form-control" name="usubtitle" value="<?= htmlspecialchars($about['subtitle'] ?? '') ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Years Experience</label>
                                        <input type="number" class="form-control" name="uyears" value="<?= (int)($about['years_experience'] ?? 20) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">YouTube Video ID (example: Ynr4o0eOjdg)</label>
                                        <input type="text" class="form-control" name="uvideo" value="<?= htmlspecialchars($about['video_url'] ?? '') ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Main About Image</label><br>
                                        <?php if (!empty($about['image'])): ?>
                                            <img src="upload/<?= htmlspecialchars($about['image']) ?>" style="max-width:200px; margin-bottom:10px;">
                                        <?php endif; ?>
                                        <input class="form-control" name="aimage" type="file">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Small About Image (for video box)</label><br>
                                        <?php if (!empty($about['image2'])): ?>
                                            <img src="upload/<?= htmlspecialchars($about['image2']) ?>" style="max-width:200px; margin-bottom:10px;">
                                        <?php endif; ?>
                                        <input class="form-control" name="aimage2" type="file">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Content</label>
                                        <textarea class="form-control" name="ucontent" rows="10"><?= htmlspecialchars($about['content'] ?? '') ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary" name="update">Save</button>

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
