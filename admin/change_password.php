<?php
include_once("config.php");

// Handle user information update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $user_id = intval($_POST['aid']);
    $auser = $_POST['auser'];
    $aemail = $_POST['aemail'];
    $apass = $_POST['apass'] ? password_hash($_POST['apass'], PASSWORD_DEFAULT) : $_POST['current_apass'];
    $aphone = $_POST['aphone'];

    $query = "UPDATE admin SET auser = ?, aemail = ?, apass = ?, aphone = ? WHERE aid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssi", $auser, $aemail, $apass, $aphone, $user_id);

    if ($stmt->execute()) {
        $success_message = "User updated successfully.";
    } else {
        $error_message = "Error updating user: " . $stmt->error;
    }
    $stmt->close();
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $aid = intval($_POST['aid']);
    $new_password = $_POST['new_password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $query = "UPDATE admin SET apass = ? WHERE aid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $hashed_password, $aid);

    if ($stmt->execute()) {
        $success_message = "Password changed successfully.";
    } else {
        $error_message = "Error changing password: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all users
$query = "SELECT aid, auser, aemail, apass, aphone FROM admin";
$result = $conn->query($query);
$users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .user-table {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        .action-btn {
            padding: 5px 10px;
            margin: 0 5px;
            font-size: 0.85rem;
        }
        .modal-header {
            background-color: #007bff;
            color: white;
        }
        .success-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            animation: fadeInOut 3s ease-in-out;
        }
        @keyframes fadeInOut {
            0% { opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
        }
        .search-container {
            margin-bottom: 20px;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="fas fa-users-cog me-2"></i>User Management</h2>
           <button class="btn btn-primary" onclick="window.location.href='dashboard.php'">
    <i class="fas fa-arrow-left me-2"></i>Return to Dashboard
</button>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success success-alert alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger success-alert alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="search-container">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="searchInput" placeholder="Search users...">
            </div>
        </div>

        <div class="table-responsive user-table p-3">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['aid']); ?></td>
                        <td><?php echo htmlspecialchars($user['auser']); ?></td>
                        <td><?php echo htmlspecialchars($user['aemail']); ?></td>
                        <td><?php echo htmlspecialchars($user['aphone']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning action-btn edit-user" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editUserModal"
                                    data-id="<?php echo $user['aid']; ?>"
                                    data-user="<?php echo htmlspecialchars($user['auser']); ?>"
                                    data-email="<?php echo htmlspecialchars($user['aemail']); ?>"
                                    data-phone="<?php echo htmlspecialchars($user['aphone']); ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger action-btn change-password" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#changePasswordModal"
                                    data-id="<?php echo $user['aid']; ?>"
                                    data-user="<?php echo htmlspecialchars($user['auser']); ?>">
                                <i class="fas fa-key"></i> Password
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="modal-body">
                        <input type="hidden" name="aid" id="editUserId">
                        <input type="hidden" name="current_apass" id="currentPassword">
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" name="auser" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="aemail" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="editPhone" name="aphone" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="editPassword" name="apass">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="modal-body">
                        <input type="hidden" name="aid" id="passwordUserId">
                        <p>Changing password for: <strong id="passwordUsername"></strong></p>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                            <div class="form-text">Password must be at least 8 characters long</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="change_password" class="btn btn-primary" id="submitPasswordChange">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add User Modal (Placeholder - Implement functionality as needed) -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="newUsername" name="auser" required>
                        </div>
                        <div class="mb-3">
                            <label for="newEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="newEmail" name="aemail" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="newPhone" name="aphone" required>
                        </div>
                        <div class="mb-3">
                            <label for="newUserPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="newUserPassword" name="apass" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit User Modal Handler
        document.querySelectorAll('.edit-user').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const username = this.getAttribute('data-user');
                const email = this.getAttribute('data-email');
                const phone = this.getAttribute('data-phone');
                
                document.getElementById('editUserId').value = userId;
                document.getElementById('editUsername').value = username;
                document.getElementById('editEmail').value = email;
                document.getElementById('editPhone').value = phone;
                document.getElementById('currentPassword').value = '<?php echo isset($user['apass']) ? $user['apass'] : ''; ?>';
            });
        });

        // Change Password Modal Handler
        document.querySelectorAll('.change-password').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const username = this.getAttribute('data-user');
                
                document.getElementById('passwordUserId').value = userId;
                document.getElementById('passwordUsername').textContent = username;
            });
        });

        // Password Confirmation Validation
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = this.value;
            const submitButton = document.getElementById('submitPasswordChange');
            
            if (newPassword !== confirmPassword) {
                this.classList.add('is-invalid');
                submitButton.disabled = true;
            } else {
                this.classList.remove('is-invalid');
                submitButton.disabled = false;
            }
        });

        // Search Functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });

        // Auto-close alerts after 3 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 3000);
    </script>
</body>
</html>