<?php
include 'config.php'; // Ensure the database connection file is correctly included

// Check if the POST data is set
if (isset($_POST['aid'], $_POST['auser'], $_POST['aemail'], $_POST['adob'], $_POST['aphone'])) {
    $aid = $_POST['aid'];
    $auser = $_POST['auser'];
    $aemail = $_POST['aemail'];
    $adob = $_POST['adob'];
    $aphone = $_POST['aphone'];

    // Prepare an SQL statement to update admin data
    $query = $con->prepare("UPDATE admin SET auser = ?, aemail = ?, adob = ?, aphone = ? WHERE aid = ?");
    $query->bind_param("ssssi", $auser, $aemail, $adob, $aphone, $aid);

    if ($query->execute()) {
        // Success, prepare to redirect and show a popup message
        $query->close();
        $con->close();
        echo "<script>
            alert('Admin updated successfully!');
            window.location.href = 'dashboard.php'; // Redirect to the dashboard or relevant page
        </script>";
    } else {
        // Error, display the error message
        echo "Error updating record: " . $con->error;
    }
} else {
    // Not all data was sent to this script
    echo "All fields are required.";
}

// Close the prepared statement and the database connection if still open
if (isset($query)) {
    $query->close();
}
if (isset($con)) {
    $con->close();
}
?>
