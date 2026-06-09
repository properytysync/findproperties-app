<?php
session_start();
require("../config.php");

if (!isset($_SESSION['auser'])) {
    header("location:index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sname = trim($_POST['sname']);
    
    // Validate input
    if (empty($sname)) {
        $error = "State name is required";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] != UPLOAD_ERR_OK) {
        $error = "Image is required";
    } else {
        // Process image upload
        $target_dir = "../uploads/states/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $filename;
        
        // Validate image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check === false) {
            $error = "File is not an image";
        } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $error = "Error uploading file";
        } else {
            // Insert into database
            $image_path = "uploads/states/" . $filename;
            
            // Check if the table has an auto-increment ID
            $table_info = $con->query("SHOW COLUMNS FROM state");
            $has_auto_increment = false;
            while ($column = $table_info->fetch_assoc()) {
                if ($column['Extra'] == 'auto_increment') {
                    $has_auto_increment = true;
                    break;
                }
            }
            
            if ($has_auto_increment) {
                // Let the database handle the auto-increment ID
                $stmt = $con->prepare("INSERT INTO state (sname, image_path) VALUES (?, ?)");
                $stmt->bind_param("ss", $sname, $image_path);
            } else {
                // Manually get the next ID if no auto-increment
                $result = $con->query("SELECT MAX(sid) as max_id FROM state");
                $row = $result->fetch_assoc();
                $next_id = $row['max_id'] + 1;
                
                $stmt = $con->prepare("INSERT INTO state (sid, sname, image_path) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $next_id, $sname, $image_path);
            }
            
            if ($stmt->execute()) {
                $success = "Location added successfully!";
                $sname = '';
            } else {
                $error = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Location</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container bg-white">
            <h2 class="mb-4">Add New Location</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="sname" class="form-label">Location Name</label>
                    <input type="text" class="form-control" id="sname" name="sname" 
                           value="<?php echo isset($sname) ? htmlspecialchars($sname) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="image" class="form-label">Location Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                    <img id="imagePreview" class="preview-image" alt="Image preview">
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Add Location</button>
                    <a href="pplace.php" class="btn btn-secondary">Back to List</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>