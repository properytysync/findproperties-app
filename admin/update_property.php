<?php
// Include the database connection file
include 'config.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pid = intval($_POST['pid']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $pcontent = mysqli_real_escape_string($conn, $_POST['pcontent']);
    $price = floatval($_POST['price']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);

    // Prepare SQL query
    $update_query = "UPDATE property SET 
        title = '$title', 
        pcontent = '$pcontent', 
        price = '$price', 
        location = '$location', 
        city = '$city' 
        WHERE pid = '$pid'";

    // Handle image upload
    $uploads_dir = 'property/';
    $image_columns = ['pimage', 'pimage1', 'pimage2', 'pimage3', 'pimage4'];

    foreach ($image_columns as $image_column) {
        if (isset($_FILES[$image_column]) && $_FILES[$image_column]['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES[$image_column]['tmp_name'];
            $name = basename($_FILES[$image_column]['name']);
            $upload_file = $uploads_dir . $name;

            if (move_uploaded_file($tmp_name, $upload_file)) {
                // Update the image path in the database
                $update_query = str_replace($image_column . " = ''", $image_column . " = '$name'", $update_query);
            } else {
                echo "Failed to upload image.";
            }
        }
    }

    // Execute the update query
    if (mysqli_query($conn, $update_query)) {
        // Redirect to property view with success message
        header("Location: propertyview.php?msg=Property updated successfully.");
        exit();
    } else {
        echo "Error updating property: " . mysqli_error($conn);
    }
} else {
    // If not a POST request, redirect back to property view
    header("Location: propertyview.php?msg=Invalid request.");
    exit();
}
?>
