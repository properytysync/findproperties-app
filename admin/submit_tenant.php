<?php
include 'config.php'; // Ensure this path is correct for database connection

// Collect all required data from the form
$pid = $_POST['property_id'];
$name = $_POST['name'];
$contact_info = $_POST['contact_info'];
$tenant_type = $_POST['tenant_type'];
$lease_start = !empty($_POST['lease_start']) ? $_POST['lease_start'] : NULL; // Set to NULL if empty
$lease_end = !empty($_POST['lease_end']) ? $_POST['lease_end'] : NULL; // Set to NULL if empty
$purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : NULL; // Set to NULL if empty
$agent_id = $_POST['agent_id'];

// Get price of the property
$priceQuery = "SELECT price FROM property WHERE pid = ?";
$priceStmt = $con->prepare($priceQuery);
if ($priceStmt === false) {
    die("Error preparing price query: " . $con->error);
}

$priceStmt->bind_param("i", $pid);
$priceStmt->execute();
$priceStmt->bind_result($price);
$priceStmt->fetch();
$priceStmt->close();

// Check if the price is fetched properly
if (empty($price)) {
    echo "<script>alert('No price found for the selected property.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Proceed with adding the tenant
$tenantQuery = "INSERT INTO tenants (pid, name, contact_info, tenant_type, lease_start, lease_end, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
$tenantStmt = $con->prepare($tenantQuery);
if ($tenantStmt === false) {
    die("Error preparing tenant query: " . $con->error);
}

$tenantStmt->bind_param("issssss", $pid, $name, $contact_info, $tenant_type, $lease_start, $lease_end, $purchase_date);
$tenantResult = $tenantStmt->execute();

if ($tenantResult) {
    $tenant_id = $tenantStmt->insert_id;  // Get the newly created tenant ID
    $tenantStmt->close();

    // Update property status to sold
    $updateProperty = "UPDATE property SET status = 'sold out' WHERE pid = ?";
    $updateStmt = $con->prepare($updateProperty);
    if ($updateStmt === false) {
        die("Error preparing update property query: " . $con->error);
    }

    $updateStmt->bind_param("i", $pid);
    $updateStmt->execute();
    $updateStmt->close();

    // Insert transaction record
    $transactionQuery = "INSERT INTO transactions (pid, tenant_id, agent_id, amount, transaction_type, transaction_date) VALUES (?, ?, ?, ?, 'sale', NOW())";
    $transactionStmt = $con->prepare($transactionQuery);
    if ($transactionStmt === false) {
        die("Error preparing transaction query: " . $con->error);
    }

    $transactionStmt->bind_param("iiid", $pid, $tenant_id, $agent_id, $price);
    $transactionStmt->execute();
    $transactionStmt->close();

    // Redirect to dashboard with a success message
    echo "<script>alert('Tenant added and property updated successfully.'); window.location.href='dashboard.php';</script>";
} else {
    // Handle failure
    echo "Error adding tenant information: " . $tenantStmt->error;
}

$con->close();
?>
