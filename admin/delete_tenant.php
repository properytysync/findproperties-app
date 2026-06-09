<?php
session_start();
require("config.php");

if (!isset($_SESSION['auser'])) {
    header("location:index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tenant_id'])) {
    $tenant_id = $_POST['tenant_id'];

    // Delete transactions related to the tenant
    $deleteTransactionsQuery = "DELETE FROM transactions WHERE tenant_id = ?";
    $deleteTransactionsStmt = $con->prepare($deleteTransactionsQuery);
    $deleteTransactionsStmt->bind_param("i", $tenant_id);
    
    if ($deleteTransactionsStmt->execute()) {
        $deleteTransactionsStmt->close();

        // Proceed with deleting the tenant
        $deleteTenantQuery = "DELETE FROM tenants WHERE tenant_id = ?";
        $deleteTenantStmt = $con->prepare($deleteTenantQuery);
        $deleteTenantStmt->bind_param("i", $tenant_id);

        if ($deleteTenantStmt->execute()) {
            // Successfully deleted tenant
            echo "<script>alert('Tenant deleted successfully.'); window.location.href='display_tenants.php';</script>";
            exit;
        } else {
            // Error deleting tenant
            echo "Error deleting tenant: " . $deleteTenantStmt->error;
        }

        $deleteTenantStmt->close();
    } else {
        // Error deleting transactions
        echo "Error deleting transactions: " . $deleteTransactionsStmt->error;
    }

    $con->close();
} else {
    // Redirect to dashboard or error page if tenant_id is not provided
    header("location:display_tenants.php");
    exit;
}
?>