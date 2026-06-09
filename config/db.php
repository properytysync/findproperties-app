<?php
// /config/db.php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "propertysync";

$con = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$con->set_charset("utf8mb4");
