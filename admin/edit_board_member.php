<?php
session_start();
include("../config.php"); // Ensure this file properly connects to the database

// Initialize member variable
$member = null;

// Get board member details
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Fetch member details
    $query = $conn->prepare("SELECT * FROM board_members WHERE id = ?");
    $query->bind_param("i", $id);
    $query->execute();
    $result = $query->get_result();
    $member = $result->fetch_assoc();

    if (!$member) {
        die("Board member not found.");
    }
} else {
    die("Invalid ID.");
}

// Handle form submission for updating details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $position = trim($_POST["position"]);
    $description = trim($_POST["description"]);
    
    $imagePath = $member["image"]; // Default to existing image

    // If a new image is uploaded
    if (!empty($_FILES["image"]["name"])) {
        $target_dir = "uploads/board_members/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $image = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Validate image type
        $allowed_types = ["jpg", "jpeg", "png"];
        if (!in_array($imageFileType, $allowed_types)) {
            die("Invalid file format. Only JPG, JPEG, and PNG are allowed.");
        }

        // Delete old image before saving a new one
        if (!empty($member["image"]) && file_exists($member["image"])) {
            unlink($member["image"]);
        }

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $imagePath = $target_file;
        } else {
            die("Failed to upload image.");
        }
    }

    // Update board member details
    $updateQuery = $conn->prepare("UPDATE board_members SET name=?, position=?, description=?, image=? WHERE id=?");
    $updateQuery->bind_param("ssssi", $name, $position, $description, $imagePath, $id);
    
    if ($updateQuery->execute()) {
        header("Location: add_board_member.php");
        exit();
    } else {
        echo "Error updating record.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Board Member</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 600px; margin-top: 50px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        .img-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 5px; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center mb-4">Edit Board Member</h2>
    
    <?php if ($member): ?>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Name:</label>
            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($member['name']); ?>" required>
        </div>
        
        <div class="mb-3">
            <label for="position" class="form-label">Position:</label>
            <input type="text" class="form-control" name="position" value="<?= htmlspecialchars($member['position']); ?>" required>
        </div>
        
        <div class="mb-3">
            <label for="description" class="form-label">Description:</label>
            <textarea class="form-control" name="description" required><?= htmlspecialchars($member['description']); ?></textarea>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Current Image:</label><br>
            <?php if (!empty($member["image"])): ?>
                <img src="<?= htmlspecialchars($member["image"]); ?>" class="img-preview">
            <?php else: ?>
                <p>No image available.</p>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Upload New Image:</label>
            <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png">
        </div>

        <button type="submit" class="btn btn-primary w-100">Update</button>
    </form>
    <?php else: ?>
        <p class="text-danger">Board member details not found.</p>
    <?php endif; ?>
</div>

</body>
</html>
