<?php
include 'config.php';

$id = $_POST['id'];

$query = "DELETE FROM crm_leads WHERE id=$id";
mysqli_query($conn, $query);
echo "Success";
?>
