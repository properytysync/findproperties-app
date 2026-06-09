<?php
session_start();
require("config.php");

if (!isset($_SESSION['auser'])) {
    header("location:index.php");
    exit;
}

$sid = $_GET['sid'];

$deleteQuery = "DELETE FROM state WHERE sid='$sid'";
if ($con->query($deleteQuery)) {
    header("location:pplace.php");
} else {
    echo "Error: " . $con->error;
}
?>
