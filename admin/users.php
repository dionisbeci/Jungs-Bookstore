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

// Handle user type update
if (isset($_POST['update_user_type'])) {
    $user_id = $_POST['user_id'];
    $user_type = $_POST['user_type'];
    
    // Don't allow changing own type
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot change your own user type.";
    } else {
        $sql = "UPDATE users SET user_type = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $user_type, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "User type updated successfully.";
            } else {
                $error = "Error updating user type.";
            }
        }
    }
}

// Get all users except current admin
$sql = "SELECT * FROM users WHERE id != ? ORDER BY username";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $users = mysqli_stmt_get_result($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Jungs Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="books.php">
                                <i class="fas fa-book"></i> Manage Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="users.php">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="orders.php">
                                <i class="fas fa-shopping-cart"></i> Manage Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="categories.php">
                                <i class="fas fa-tags"></i> Manage Categories
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Users</h1>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Users Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Profile</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>User Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $user['profile_picture'] ? '../' . $user['profile_picture'] : '../assets/images/default-profile.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($user['username']); ?>"
                                             class="rounded-circle"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    </td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <form action="" method="post" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="user_type" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                                <option value="user" <?php echo $user['user_type'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $user['user_type'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <input type="hidden" name="update_user_type" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <form action="" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html> 