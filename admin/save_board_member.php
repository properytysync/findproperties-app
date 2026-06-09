<?php
include("config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $position = $_POST['position'];
    $description = $_POST['description'];

    // Handle image upload
    $image = $_FILES['image']['name'];
    $target = "admin/upload/board_members/" . basename($image);
    move_uploaded_file($_FILES['image']['tmp_name'], $target);

    // Insert into database
    $query = "INSERT INTO board_members (name, position, image, description) VALUES (?, ?, ?, ?)";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ssss", $name, $position, $image, $description);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Board member added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>