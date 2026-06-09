<?php
session_start();
include("../config.php"); // Ensure correct database connection

// Handle form submission for adding a new board member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_member'])) {
    $name = $_POST["name"];
    $position = $_POST["position"];
    $description = $_POST["description"];

    // File upload handling
    $target_dir = "uploads/board_members/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true); // Ensure folder exists
    }
    
    $image = $_FILES["image"]["name"];
    $target_file = $target_dir . basename($image);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate image format
    $allowed_types = ["jpg", "jpeg", "png"];
    if (!in_array($imageFileType, $allowed_types)) {
        die("Invalid file format. Only JPG, JPEG, and PNG are allowed.");
    }

    // Move uploaded file
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO board_members (name, position, description, image) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $position, $description, $target_file);
        $stmt->execute();
    } else {
        die("Failed to upload image.");
    }
}

// Fetch existing board members
$members = $conn->query("SELECT * FROM board_members");

// Handle delete request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM board_members WHERE id=$id");
    header("Location: add_board_member.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Board Members</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 800px; margin-top: 50px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        .form-group { margin-bottom: 15px; }
        .btn-primary, .btn-danger { width: 100%; }
        .back-btn { margin-top: 15px; display: block; text-align: center; }
        .img-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center mb-4">Add Board Member</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="fw-bold">Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="fw-bold">Position</label>
            <input type="text" name="position" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="fw-bold">Profile Image</label>
            <input type="file" name="image" class="form-control" accept="image/*" required>
            <small class="text-muted">Allowed formats: JPG, JPEG, PNG (Max: 2MB)</small>
        </div>
        <div class="form-group">
            <label class="fw-bold">Description</label>
            <textarea name="description" class="form-control" rows="5" required></textarea>
        </div>
        <button type="submit" name="add_member" class="btn btn-primary">Save Board Member</button>
    </form>
    <a href="dashboard.php" class="btn btn-outline-secondary back-btn">← Back to Dashboard</a>

    <hr>

    <h3 class="text-center mt-4">Manage Board Members</h3>
    <table class="table table-striped mt-3">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Position</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $members->fetch_assoc()): ?>
                <tr>
                    <td><img src="<?= $row['image'] ?>" class="img-preview"></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['position']) ?></td>
                    <td>
                        <a href="edit_board_member.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
