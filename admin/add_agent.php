<?php
// admin/add_agent.php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/config.php";

require_admin(); // ✅ only admins can create agents

$error = "";
$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add"])) {

    $name = trim($_POST["name"] ?? "");
    $contact_info = trim($_POST["contact_info"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = (string)($_POST["password"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $facebook_username = trim($_POST["facebook_username"] ?? "");
    $instagram_username = trim($_POST["instagram_username"] ?? "");

    if ($name === "" || $contact_info === "") {
        $error = "<p class='alert alert-warning'>Name and Contact Info are required.</p>";
    } elseif ($email === "") {
        $error = "<p class='alert alert-warning'>Email is required (agents will login with email).</p>";
    } elseif (strlen($password) < 6) {
        $error = "<p class='alert alert-warning'>Password must be at least 6 characters.</p>";
    }

    // Upload optional (store inside admin/ragents/)
    $picturePath = "";
    if ($error === "" && !empty($_FILES["picture"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["picture"]["name"], PATHINFO_EXTENSION));
        $allowed = ["jpg","jpeg","png","webp"];

        if (!in_array($ext, $allowed, true)) {
            $error = "<p class='alert alert-warning'>Picture must be JPG, PNG, or WEBP.</p>";
        } else {
            // ✅ Important: your old images are in admin/ragents/...
            $uploadDir = __DIR__ . "/ragents/";
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

            $newName = "agent_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            $destFs = $uploadDir . $newName;

            if (move_uploaded_file($_FILES["picture"]["tmp_name"], $destFs)) {
                // store relative path (for HTML)
                $picturePath = "ragents/" . $newName;
            } else {
                $error = "<p class='alert alert-warning'>Failed to upload picture.</p>";
            }
        }
    }

    if ($error === "") {
        // unique email check (recommended even if no SQL unique constraint)
        $stmt = $con->prepare("SELECT agent_id FROM agents WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $error = "<p class='alert alert-warning'>An agent with that email already exists.</p>";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $con->prepare("
                INSERT INTO agents
                    (name, contact_info, email, password_hash, description, facebook_username, instagram_username, picture, is_active)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->bind_param(
                "ssssssss",
                $name, $contact_info, $email, $password_hash,
                $description, $facebook_username, $instagram_username, $picturePath
            );

            if ($stmt->execute()) {
                header("location: agents_list.php?msg=" . urlencode("Agent added successfully."));
                exit();
            } else {
                $error = "<p class='alert alert-danger'>Insert failed: " . h($stmt->error) . "</p>";
            }
            $stmt->close();
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

  <title>Add Agent</title>
  <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
  <link rel="stylesheet" type="text/css" href="assets/css/style.css">
  <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    .page-card{background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.08);padding:24px;}
  </style>
</head>

<body>
<div class="page-wrapper compact-wrapper" id="pageWrapper">
  <div class="page-body-wrapper">

    <div class="sidebar-wrapper" data-layout="fill-svg">
      <div>
        <?php include('menu.php'); ?>
      </div>
    </div>

    <div class="page-body">
      <div class="container-fluid">

        <div class="page-title">
          <div class="row">
            <div class="col-sm-6 p-0">
              <h3>Add Agent</h3>
              <div class="text-muted">Create a new agent profile</div>
            </div>
          </div>
        </div>

        <?php echo $error; ?>
        <?php echo $msg; ?>

        <div class="page-card">
          <form method="post" enctype="multipart/form-data">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name *</label>
                <input class="form-control" type="text" name="name" required value="<?= h($_POST['name'] ?? '') ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">Contact Info *</label>
                <input class="form-control" type="text" name="contact_info" required value="<?= h($_POST['contact_info'] ?? '') ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input class="form-control" type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>">
                <div class="form-text">Agent will login with this email.</div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Password *</label>
                <input class="form-control" type="password" name="password" required minlength="6">
                <div class="form-text">Minimum 6 characters.</div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Profile Picture (optional)</label>
                <input class="form-control" type="file" name="picture" accept="image/*">
                <div class="form-text">Saved to <b>admin/ragents/</b> (same as your old images).</div>
              </div>

              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="4"><?= h($_POST['description'] ?? '') ?></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label">Facebook Username</label>
                <input class="form-control" type="text" name="facebook_username" value="<?= h($_POST['facebook_username'] ?? '') ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">Instagram Username</label>
                <input class="form-control" type="text" name="instagram_username" value="<?= h($_POST['instagram_username'] ?? '') ?>">
              </div>

              <div class="col-12">
                <button class="btn btn-success" type="submit" name="add" value="1">Add Agent</button>
                <a class="btn btn-light" href="agents_list.php">Cancel</a>
              </div>
            </div>
          </form>
        </div>

      </div>
    </div>

  </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
