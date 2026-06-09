<?php
include 'config.php';

// Get agent ID from query string
$agent_id = $_GET['id'];

// Delete agent from database
$query = "DELETE FROM agents WHERE agent_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $agent_id);
if ($stmt->execute()) {
    $message = "Agent successfully deleted. Redirecting to dashboard...";
    echo "<script>setTimeout(function() { window.location.href = 'dashboard.php'; }, 3000);</script>";
} else {
    $message = "Error deleting agent: " . $stmt->error;
}

$stmt->close();
$con->close();

// Display the message and a redirect script
echo $message;
?>
