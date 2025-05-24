<?php
session_start();
require_once '../config/database.php';

// Include the admin header
include 'includes/header.php';

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "admin") {
    header("location: ../login.php");
    exit;
}

$error = '';
$success = '';

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    // Don't allow deleting self
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Delete user's wishlist items
            $sql = "DELETE FROM wishlist WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                throw new Exception("Error preparing wishlist deletion: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error executing wishlist deletion: " . mysqli_stmt_error($stmt));
            }
            
            // Delete user's order items
            $sql = "DELETE oi FROM order_items oi 
                    INNER JOIN orders o ON oi.order_id = o.id 
                    WHERE o.user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                throw new Exception("Error preparing order items deletion: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error executing order items deletion: " . mysqli_stmt_error($stmt));
            }
            
            // Delete user's orders
            $sql = "DELETE FROM orders WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                throw new Exception("Error preparing orders deletion: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error executing orders deletion: " . mysqli_stmt_error($stmt));
            }
            
            // Delete user's profile picture if exists
            $sql = "SELECT profile_picture FROM users WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                
                if ($user && $user['profile_picture'] && file_exists('../' . $user['profile_picture'])) {
                    unlink('../' . $user['profile_picture']);
                }
            }
            
            // Finally, delete the user
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                throw new Exception("Error preparing user deletion: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error executing user deletion: " . mysqli_stmt_error($stmt));
            }
            
            // Commit transaction
            if (!mysqli_commit($conn)) {
                throw new Exception("Error committing transaction: " . mysqli_error($conn));
            }
            
            $success = "User and all associated data deleted successfully.";
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "Error deleting user: " . $e->getMessage();
            error_log("User deletion error: " . $e->getMessage());
        }
    }
}

// Handle user update
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $user_type = $_POST['user_type'];
    $password = trim($_POST['password']);
    
    // Don't allow changing own type
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot change your own user type.";
    } else {
        if (!empty($password)) {
            // Update with new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET user_type = ?, password = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssi", $user_type, $hashed_password, $user_id);
            }
        } else {
            // Update only user type
            $sql = "UPDATE users SET user_type = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $user_type, $user_id);
            }
        }
        
        if (isset($stmt) && mysqli_stmt_execute($stmt)) {
            $success = "User updated successfully.";
        } else {
            $error = "Error updating user.";
        }
    }
}

// Get all users
$sql = "SELECT * FROM users ORDER BY id DESC";
$users = mysqli_query($conn, $sql);
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Users</h1>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Profile</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <img src="<?php echo $user['profile_picture'] ? '../' . $user['profile_picture'] : '../assets/images/no-image.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($user['username']); ?>"
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                        </td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo ucfirst($user['user_type']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="post">
                <input type="hidden" name="update_user" value="1">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <small class="text-muted">Leave empty to keep current password</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_user_type" class="form-label">User Type</label>
                        <select class="form-select" id="edit_user_type" name="user_type" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_user_type').value = user.user_type;
    
    // Disable user type selection for own account
    const userTypeSelect = document.getElementById('edit_user_type');
    userTypeSelect.disabled = user.id === <?php echo $_SESSION['user_id']; ?>;
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="delete_user" value="1">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html> 