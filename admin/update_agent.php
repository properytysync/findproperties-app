<?php
include 'config.php';

$message = '';

// Check if agent_id and form data are received
if (isset($_POST['agent_id'], $_POST['name'], $_POST['contact_info'])) {
    $agent_id = $_POST['agent_id'];
    $name = $_POST['name'];
    $contact_info = $_POST['contact_info'];

    // Prepare the SQL query to update the agent details
    $query = "UPDATE agents SET name = ?, contact_info = ? WHERE agent_id = ?";
    $stmt = $con->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ssi", $name, $contact_info, $agent_id);
        if ($stmt->execute()) {
            $message = "Agent details updated successfully. Redirecting to dashboard...";
            header("refresh:3;url=dashboard.php");
        } else {
            $message = "Error updating agent details: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $con->error;
    }
} else {
    $message = "Required data not provided.";
}

$con->close();
echo $message;
?>
