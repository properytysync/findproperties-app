<?php
include_once("config.php"); // Include your database configuration file

if (isset($_GET['pid'])) {
    $property_id = intval($_GET['pid']); // Ensure the ID is an integer

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Set pid to NULL for associated tenants
        $updateTenantsQuery = "UPDATE tenants SET pid = NULL WHERE pid = ?";
        $updateStmt = $conn->prepare($updateTenantsQuery);
        
        if ($updateStmt) {
            $updateStmt->bind_param("i", $property_id);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            throw new Exception("Error preparing update statement: " . $conn->error);
        }

        // Delete the property
        $deleteQuery = "DELETE FROM property WHERE pid = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        
        if ($deleteStmt) {
            $deleteStmt->bind_param("i", $property_id);
            if ($deleteStmt->execute()) {
                // Commit the transaction
                $conn->commit();
                // Show bold success message and redirect
                echo "<div style='text-align:center; margin-top:50px; font-size:20px; color:green; font-weight:bold;'>
                        Property deleted successfully.
                      </div>";
                echo "<script>
                        setTimeout(function(){
                            window.location.href = 'propertyview.php';
                        }, 3000);
                      </script>";
                exit;
            } else {
                throw new Exception("Error deleting property: " . $deleteStmt->error);
            }
            $deleteStmt->close();
        } else {
            throw new Exception("Error preparing delete statement: " . $conn->error);
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo "<div style='text-align:center; margin-top:50px; font-size:20px; color:red; font-weight:bold;'>
                Transaction failed: " . htmlspecialchars($e->getMessage()) . "
              </div>";
    }
} else {
    echo "<div style='text-align:center; margin-top:50px; font-size:20px; color:red; font-weight:bold;'>
            No property ID specified.
          </div>";
}

$conn->close();
?>
