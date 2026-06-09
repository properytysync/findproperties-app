<?php
include 'config.php';

$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$source = $_POST['source'];

$query = "INSERT INTO crm_leads (name, email, phone, source) VALUES ('$name', '$email', '$phone', '$source')";
mysqli_query($conn, $query);
echo "Success";
?>
