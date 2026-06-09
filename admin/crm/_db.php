<?php
// /admin/crm/_db.php
require_once __DIR__ . "/../config.php"; // you said config.php is inside admin

// You already create both $conn (mysqli object) and $con (procedural).
// We'll use $conn everywhere.
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("CRM Error: $conn database connection not found. Check admin/config.php include path.");
}

$conn->set_charset("utf8mb4");
