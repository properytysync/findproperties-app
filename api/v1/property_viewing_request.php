<?php
require_once __DIR__ . "/_helpers.php";

require_method("POST");

header("Content-Type: application/json; charset=utf-8");

$input = json_decode(file_get_contents("php://input"), true);

$name           = trim((string)($input["name"] ?? ""));
$email          = trim((string)($input["email"] ?? ""));
$phone          = trim((string)($input["phone"] ?? ""));
$subject        = trim((string)($input["subject"] ?? ""));
$message        = trim((string)($input["message"] ?? ""));
$pid            = (int)($input["pid"] ?? 0);
$propertyTitle  = trim((string)($input["property_title"] ?? ""));
$propertyLoc    = trim((string)($input["property_location"] ?? ""));
$reference      = trim((string)($input["reference"] ?? ""));

if ($name === "" || $email === "" || $phone === "" || $subject === "" || $message === "" || $pid <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required."
    ]);
    exit;
}

$siteQuery = mysqli_query(
    $con,
    "SELECT paystack_secret_key, viewing_fee, enable_viewing_payment
     FROM site_info
     WHERE id = 1
     LIMIT 1"
);

$site = mysqli_fetch_assoc($siteQuery) ?: [];
$enablePayment = (int)($site["enable_viewing_payment"] ?? 0);
$secretKey = trim((string)($site["paystack_secret_key"] ?? ""));

$paymentStatus = "pending";
$paymentReference = null;

if ($enablePayment === 1) {
    if ($reference === "") {
        echo json_encode([
            "status" => "error",
            "message" => "Payment reference is required."
        ]);
        exit;
    }

    if ($secretKey === "") {
        echo json_encode([
            "status" => "error",
            "message" => "Paystack secret key is not configured."
        ]);
        exit;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . rawurlencode($reference));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $secretKey,
        "Cache-Control: no-cache"
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode([
            "status" => "error",
            "message" => "Payment verification failed: " . $curlError
        ]);
        exit;
    }

    $verify = json_decode($response, true);

    if (
        empty($verify["status"]) ||
        empty($verify["data"]["status"]) ||
        $verify["data"]["status"] !== "success"
    ) {
        echo json_encode([
            "status" => "error",
            "message" => "Payment was not verified."
        ]);
        exit;
    }

    $paymentReference = $reference;
    $paymentStatus = "paid";
}

mysqli_begin_transaction($con);

try {
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
    $source = "Property Inquiry: {$propertyTitle} (PID: {$pid})" . ($propertyLoc ? " - {$propertyLoc}" : "");

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
        "message" => "Viewing request submitted successfully.",
        "data" => [
            "cid" => $cid,
            "payment_reference" => $paymentReference,
            "payment_status" => $paymentStatus
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