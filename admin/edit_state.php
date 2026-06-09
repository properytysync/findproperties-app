<?php
session_start();
require("config.php");
require_once __DIR__ . "/_auth.php";

require_login();

$sid = $_GET['sid'] ?? '';

if (!$sid) {
    echo "Invalid state ID.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sname = $_POST['sname'];
    $image_path = '';

    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
        $target_dir = "../images/thumbnail4/";
        $target_file = $target_dir . basename($_FILES['image']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $valid_extensions = array("jpg", "jpeg", "png", "gif");

        if (in_array($imageFileType, $valid_extensions)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = "images/thumbnail4/" . basename($_FILES['image']['name']);
            } else {
                echo "Error uploading file.";
                exit;
            }
        } else {
            echo "Invalid file format.";
            exit;
        }
    } else {
        $query = "SELECT image_path FROM state WHERE sid=?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        $result = $stmt->get_result();
        $state = $result->fetch_assoc();
        $image_path = $state['image_path'];
    }

    $updateQuery = "UPDATE state SET sname=?, image_path=? WHERE sid=?";
    $stmt = $con->prepare($updateQuery);
    $stmt->bind_param("ssi", $sname, $image_path, $sid);

    if ($stmt->execute()) {
        header("location:pplace.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
} else {
    $query = "SELECT * FROM state WHERE sid=?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $result = $stmt->get_result();
    $state = $result->fetch_assoc();

    if (!$state) {
        echo "State not found.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit State</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="file"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        img {
            display: block;
            margin: 0 auto 20px auto;
            max-width: 100px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit State</h1>
        <form method="post" enctype="multipart/form-data">
            <label for="sname">State Name:</label>
            <input type="text" name="sname" value="<?= htmlspecialchars($state['sname']); ?>" required>
            <label for="image">Image:</label>
            <input type="file" name="image">
            <?php if ($state['image_path']): ?>
                <img src="../<?= htmlspecialchars($state['image_path']); ?>" alt="<?= htmlspecialchars($state['sname']); ?>">
            <?php endif; ?>
            <button type="submit">Update State</button>
        </form>
    </div>
</body>
</html>
