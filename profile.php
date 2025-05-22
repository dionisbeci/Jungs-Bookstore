<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$success = $error = '';

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle account deletion
    if (isset($_POST['delete_account'])) {
        $user_id = $_SESSION["user_id"];
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
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
            if ($user['profile_picture'] && file_exists($user['profile_picture'])) {
                if (!unlink($user['profile_picture'])) {
                    throw new Exception("Error deleting profile picture file");
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
            
            // Destroy session and redirect to home
            session_destroy();
            header("location: index.php");
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "Error deleting account: " . $e->getMessage();
            // Log the error for debugging
            error_log("Account deletion error: " . $e->getMessage());
        }
    }
    // Handle profile update only if it's not a delete account request
    else {
        $name = trim($_POST['name']);
        $surname = trim($_POST['surname']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate current password if trying to change password
        if (!empty($new_password)) {
            if (!password_verify($current_password, $user['password'])) {
                $error = "Current password is incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $error = "New passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $error = "Password must have at least 6 characters.";
            }
        }
        
        if (empty($error)) {
            // Handle profile picture upload
            $profile_picture = $user['profile_picture'];
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $allowed = array('jpg', 'jpeg', 'png', 'gif');
                $filename = $_FILES['profile_picture']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $upload_dir = 'uploads/profiles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $new_filename = uniqid() . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                        // Delete old profile picture
                        if ($profile_picture && file_exists($profile_picture)) {
                            unlink($profile_picture);
                        }
                        $profile_picture = $upload_path;
                    }
                }
            }
            
            // Update user information
            if (!empty($new_password)) {
                $sql = "UPDATE users SET name=?, surname=?, email=?, password=?, profile_picture=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $sql);
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "sssssi", $name, $surname, $email, $hashed_password, $profile_picture, $_SESSION["user_id"]);
            } else {
                $sql = "UPDATE users SET name=?, surname=?, email=?, profile_picture=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $name, $surname, $email, $profile_picture, $_SESSION["user_id"]);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Profile updated successfully.";
                // Refresh user data
                $sql = "SELECT * FROM users WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
            } else {
                $error = "Something went wrong. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Jungs Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?php echo $user['profile_picture'] ? $user['profile_picture'] : 'assets/images/default-profile.jpg'; ?>" 
                             alt="Profile Picture" 
                             class="rounded-circle mb-3 profile-picture">
                        <h4><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></h4>
                        <p class="text-muted"><?php echo ucfirst($user['user_type']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="surname" class="form-label">Surname</label>
                                <input type="text" class="form-control" id="surname" name="surname" value="<?php echo htmlspecialchars($user['surname']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            </div>
                            
                            <hr>
                            
                            <h6>Change Password</h6>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                Delete Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger">Warning: This action cannot be undone!</p>
                    <p>Deleting your account will:</p>
                    <ul>
                        <li>Delete your order history</li>
                        <li>Remove your profile picture</li>
                        <li>Remove your account and all associated data</li>
                    </ul>
                    <p>Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="" method="post" class="d-inline">
                        <button type="submit" name="delete_account" class="btn btn-danger">Yes, Delete My Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js?v=123"></script>
</body>
</html> 