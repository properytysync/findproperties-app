<?php
session_start();
require("config.php");
require_once __DIR__ . "/_auth.php";

require_login();

$agent_id = (int)($_GET['id'] ?? 0);
if ($agent_id <= 0) {
    header("Location: agents_list.php?msg=" . urlencode("Invalid agent id"));
    exit();
}

// Permission
if (!can_edit_agent($agent_id)) {
    header("Location: view_agent.php?id=" . $agent_id . "&msg=" . urlencode("You can only edit your own profile"));
    exit();
}

$msg = trim($_GET['msg'] ?? '');
$error = '';
$success = '';

// Fetch agent
$st = $con->prepare("SELECT * FROM agents WHERE agent_id=? LIMIT 1");
$st->bind_param("i", $agent_id);
$st->execute();
$agent = $st->get_result()->fetch_assoc();
$st->close();

if (!$agent) {
    header("Location: agents_list.php?msg=" . urlencode("Agent not found"));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $facebook_username = trim($_POST['facebook_username'] ?? '');
    $instagram_username = trim($_POST['instagram_username'] ?? '');
    $is_active = (int)($_POST['is_active'] ?? 1);

    // Agents cannot disable themselves or others
    if (!is_admin()) {
        $is_active = (int)($agent['is_active'] ?? 1);
    }

    // Handle picture upload (optional)
    $picturePath = $agent['picture'];

    if (!empty($_FILES['picture']['name'])) {
        $target_dir = "ragents/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES["picture"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, JPEG, PNG & GIF are allowed.";
        } else {
            $newName = "agent_" . $agent_id . "_" . time() . "." . $ext;
            $target_file = $target_dir . $newName;

            if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)) {
                $picturePath = $target_file;
            } else {
                $error = "Error uploading picture.";
            }
        }
    }

    if ($error === '') {
        $sql = "UPDATE agents 
                SET name=?, contact_info=?, email=?, description=?, facebook_username=?, instagram_username=?, picture=?, is_active=?
                WHERE agent_id=?";
        $st = $con->prepare($sql);
        $st->bind_param(
            "sssssssii",
            $name, $contact_info, $email, $description, $facebook_username, $instagram_username, $picturePath, $is_active,
            $agent_id
        );

        if ($st->execute()) {
            $success = "Agent updated successfully.";

            // Refresh agent data
            $st->close();
            $st = $con->prepare("SELECT * FROM agents WHERE agent_id=? LIMIT 1");
            $st->bind_param("i", $agent_id);
            $st->execute();
            $agent = $st->get_result()->fetch_assoc();
            $st->close();

            // Update session name if agent edited self
            if (is_agent() && current_user_id() === $agent_id) {
                $_SESSION['agent_name'] = $agent['name'];
            }

        } else {
            $error = "Update failed: " . $st->error;
            $st->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Edit agent">
    <meta name="keywords" content="admin, agent">
    <meta name="author" content="pixelstrap">
    <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    <title>Edit Agent</title>
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../../css?family=Outfit:400,400i,500,500i,700,700i&amp;display=swap" rel="stylesheet">
    <link href="../../css-1?family=Roboto:300,300i,400,400i,500,500i,700,700i,900&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/icofont.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/themify.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/flag-icon.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/feather-icon.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link id="color" rel="stylesheet" href="assets/css/color-1.css" media="screen">
    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">

    <style>
      .form-card{background:#fff;border:1px solid #eee;border-radius:12px;padding:18px}
      .agent-avatar{width:70px;height:70px;border-radius:50%;object-fit:cover;border:1px solid #ddd}
    </style>
  </head>
  <body>
    <div class="loader-wrapper"><div class="theme-loader"><div class="loader-p"></div></div></div>
    <div class="tap-top"><i data-feather="chevrons-up"></i></div>

    <div class="page-wrapper compact-wrapper" id="pageWrapper">
      <div class="page-header">
        <div class="header-wrapper row m-0">
          <div class="header-logo-wrapper col-auto p-0">
            <div class="logo-wrapper"><a href="<?= is_admin() ? 'dashboard.php' : 'agents_list.php' ?>"><img class="img-fluid for-light" src="../../assets/img/logo.png" alt=""><img class="img-fluid for-dark" src="../../assets/img/logo.png" alt=""></a></div>
            <div class="toggle-sidebar">
              <svg class="sidebar-toggle"><use href="assets/svg/icon-sprite.svg#stroke-animation"></use></svg>
            </div>
          </div>

          <div class="nav-right col-xxl-7 col-xl-6 col-auto box-col-6 pull-right right-header p-0 ms-auto">
            <ul class="nav-menus">
              <li>
                <div class="mode">
                  <svg><use href="assets/svg/icon-sprite.svg#fill-dark"></use></svg>
                </div>
              </li>
              <li class="profile-nav onhover-dropdown p-0">
                <div class="d-flex align-items-center profile-media">
                  <div class="flex-grow-1">
                    <span><?= is_admin() ? 'Admin Portal' : 'Agent Portal' ?></span>
                    <p class="mb-0"><?= is_admin() ? 'Admin' : h($_SESSION['agent_name'] ?? 'Agent') ?> <i class="middle fa fa-angle-down"></i></p>
                  </div>
                </div>
                <ul class="profile-dropdown onhover-show-div">
                  <li><a href="logout.php"><i data-feather="log-in"></i><span>Logout</span></a></li>
                </ul>
              </li>
            </ul>
          </div>

        </div>
      </div>

      <div class="page-body-wrapper">
        <div class="sidebar-wrapper" data-layout="fill-svg">
          <div>
            <div class="logo-wrapper"><a href="<?= is_admin() ? 'dashboard.php' : 'agents_list.php' ?>"><img class="img-fluid" src="../../assets/img/logo.png" alt=""></a></div>
            <?php include('menu.php'); ?>
          </div>
        </div>

        <div class="page-body">
          <div class="container-fluid">
            <div class="page-title">
              <div class="row">
                <div class="col-sm-6 p-0">
                  <h3>Edit Agent</h3>
                </div>
                <div class="col-sm-6 p-0">
                  <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="agents_list.php">
                      <svg class="stroke-icon"><use href="assets/svg/icon-sprite.svg#stroke-home"></use></svg>
                    </a></li>
                    <li class="breadcrumb-item active">Edit</li>
                  </ol>
                </div>
              </div>
            </div>
          </div>

          <div class="container-fluid">
            <?php if ($msg): ?><div class="alert alert-info"><?= h($msg) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

            <div class="form-card">
              <div class="d-flex align-items-center gap-3 mb-3">
                <?php if (!empty($agent['picture']) && file_exists($agent['picture'])): ?>
                  <img class="agent-avatar" src="<?= h($agent['picture']) ?>" alt="">
                <?php else: ?>
                  <div class="agent-avatar d-flex align-items-center justify-content-center bg-light">
                    <i class="fa fa-user text-muted"></i>
                  </div>
                <?php endif; ?>

                <div>
                  <div class="fw-bold"><?= h($agent['name']) ?></div>
                  <div class="text-muted small"><?= h($agent['email']) ?></div>
                </div>
              </div>

              <form method="post" enctype="multipart/form-data">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Name</label>
                    <input class="form-control" name="name" value="<?= h($agent['name']) ?>" required>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Contact Info</label>
                    <input class="form-control" name="contact_info" value="<?= h($agent['contact_info']) ?>" required>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Email</label>
                    <input class="form-control" type="email" name="email" value="<?= h($agent['email']) ?>" required>
                  </div>

                  <?php if (is_admin()): ?>
                    <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold">Status</label>
                      <select class="form-select" name="is_active">
                        <option value="1" <?= (int)$agent['is_active'] === 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= (int)$agent['is_active'] === 0 ? 'selected' : '' ?>>Disabled</option>
                      </select>
                    </div>
                  <?php endif; ?>

                  <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Description</label>
                    <textarea class="form-control" name="description" rows="5" required><?= h($agent['description']) ?></textarea>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Facebook Username</label>
                    <input class="form-control" name="facebook_username" value="<?= h($agent['facebook_username'] ?? '') ?>">
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Instagram Username</label>
                    <input class="form-control" name="instagram_username" value="<?= h($agent['instagram_username'] ?? '') ?>">
                  </div>

                  <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Picture (optional)</label>
                    <input class="form-control" type="file" name="picture" accept="image/*">
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button class="btn btn-primary" type="submit">Save Changes</button>
                  <a href="agents_list.php" class="btn btn-outline-secondary">Back</a>
                  <?php if (can_edit_agent($agent_id)): ?>
                    <a href="change_password.php?id=<?= $agent_id ?>" class="btn btn-outline-primary">Change Password</a>
                  <?php endif; ?>
                </div>
              </form>
            </div>

          </div>

          <footer class="footer">
            <div class="container-fluid">
              <div class="row">
                <div class="col-md-6 p-0 footer-copyright">
                  <p class="mb-0">Copyright © <?= date('Y') ?> Real Estate Admin. All rights reserved.</p>
                </div>
              </div>
            </div>
          </footer>

        </div>
      </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/icons/feather-icon/feather.min.js"></script>
    <script src="assets/js/icons/feather-icon/feather-icon.js"></script>
    <script src="assets/js/script.js"></script>
  </body>
</html>
