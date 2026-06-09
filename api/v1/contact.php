<?php
require_once __DIR__ . "/_helpers.php";

require_method("POST");

header("Content-Type: application/json; charset=utf-8");

$input = json_decode(file_get_contents("php://input"), true);

$name    = trim((string)($input["name"] ?? ""));
$email   = trim((string)($input["email"] ?? ""));
$phone   = trim((string)($input["phone"] ?? ""));
$subject = trim((string)($input["subject"] ?? ""));
$message = trim((string)($input["message"] ?? ""));

if ($name === "" || $email === "" || $phone === "" || $subject === "" || $message === "") {
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required."
    ]);
    exit;
}

mysqli_begin_transaction($con);

try {
    $paymentReference = null;
    $paymentStatus = "pending";

    $stmt = mysqli_prepare(
        $con,
        "INSERT INTO contact (name, email, phone, subject, message, payment_reference, payment_status)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception("Contact prepare failed: " . mysqli_error($con));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssssss",
        $name,
        $email,
        $phone,
        $subject,
        $message,
        $paymentReference,
        $paymentStatus
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Contact insert failed: " . mysqli_stmt_error($stmt));
    }

    $cid = mysqli_insert_id($con);
    mysqli_stmt_close($stmt);

    $leadStatus = "New";
    $source = "Website Contact Form";

    $stmt2 = mysqli_prepare(
        $con,
        "INSERT INTO crm_leads (name, email, phone, status, source) VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt2) {
        throw new Exception("CRM lead prepare failed: " . mysqli_error($con));
    }

    mysqli_stmt_bind_param($stmt2, "sssss", $name, $email, $phone, $leadStatus, $source);

    if (!mysqli_stmt_execute($stmt2)) {
        throw new Exception("CRM lead insert failed: " . mysqli_stmt_error($stmt2));
    }

    mysqli_stmt_close($stmt2);

    mysqli_commit($con);

    echo json_encode([
        "status" => "success",
        "message" => "Message sent successfully.",
        "data" => [
            "cid" => $cid
        ]
    ]);
    exit;
} catch (Throwable $e) {
    mysqli_rollback($con);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}