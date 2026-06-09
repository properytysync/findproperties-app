<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_dir = "uploads/sliders/";
    $target_file = $target_dir . basename($_FILES["sliderImage"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["sliderImage"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["sliderImage"]["size"] > 500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif") {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["sliderImage"]["tmp_name"], $target_file)) {
            $imagePath = $target_file;
            $altText = $_POST['altText'];

            // Insert into database
            $insertQuery = $con->prepare("INSERT INTO sliders (image_path, alt_text) VALUES (?, ?)");
            $insertQuery->bind_param("ss", $imagePath, $altText);
            if ($insertQuery->execute()) {
                echo "The file " . htmlspecialchars(basename($_FILES["sliderImage"]["name"])) . " has been uploaded.";
            } else {
                echo "Error inserting image into database.";
            }
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Slider Image</title>
</head>
<body>
    <form action="admin_upload.php" method="post" enctype="multipart/form-data">
        Select image to upload:
        <input type="file" name="sliderImage" id="sliderImage">
        Alt Text:
        <input type="text" name="altText" id="altText">
        <input type="submit" value="Upload Image" name="submit">
    </form>
</body>
</html>
