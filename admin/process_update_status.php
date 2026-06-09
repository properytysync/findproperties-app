<?php
include 'config.php';

$id = $_POST['id'];
$status = $_POST['status'];

$query = "UPDATE crm_leads SET status='$status' WHERE id=$id";
mysqli_query($conn, $query);
echo "Success";
?>
