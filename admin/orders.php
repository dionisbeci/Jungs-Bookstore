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

// Handle order status update
if (isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
        if (mysqli_stmt_execute($stmt)) {
            // If order is rejected, return items to stock
            if ($status === 'rejected') {
                $sql = "SELECT book_id, quantity FROM order_items WHERE order_id = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $order_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    while ($item = mysqli_fetch_assoc($result)) {
                        $sql = "UPDATE books SET stock = stock + ? WHERE id = ?";
                        if ($stmt2 = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt2, "ii", $item['quantity'], $item['book_id']);
                            mysqli_stmt_execute($stmt2);
                        }
                    }
                }
            }
            $success = "Order status updated successfully.";
        } else {
            $error = "Error updating order status.";
        }
    }
}

// Handle order deletion
if (isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Return items to stock
        $sql = "SELECT book_id, quantity FROM order_items WHERE order_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($item = mysqli_fetch_assoc($result)) {
                $sql = "UPDATE books SET stock = stock + ? WHERE id = ?";
                if ($stmt2 = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt2, "ii", $item['quantity'], $item['book_id']);
                    mysqli_stmt_execute($stmt2);
                }
            }
        }
        
        // Delete order items
        $sql = "DELETE FROM order_items WHERE order_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
        }
        
        // Delete order
        $sql = "DELETE FROM orders WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        $success = "Order deleted successfully.";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = "Error deleting order: " . $e->getMessage();
    }
}

// Get all orders with user details
$sql = "SELECT o.*, u.username, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.order_date DESC";
$orders = mysqli_query($conn, $sql);
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Orders</h1>
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
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($order['username']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $order['status'] === 'completed' ? 'success' : 
                                                    ($order['status'] === 'pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
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

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetails">
                <!-- Order details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="edit_order_id">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_order" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Order Modal -->
<div class="modal fade" id="deleteOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="delete_order_id">
                    <p>Are you sure you want to delete this order? This action cannot be undone.</p>
                    <p class="text-danger">Note: This will return all items to stock.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_order" class="btn btn-danger">Delete Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewOrder(orderId) {
    // Load order details via AJAX
    fetch('get_order_details.php?id=' + orderId)
        .then(response => response.text())
        .then(data => {
            document.getElementById('orderDetails').innerHTML = data;
            new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
        });
}

function editOrder(order) {
    document.getElementById('edit_order_id').value = order.id;
    document.getElementById('status').value = order.status;
    new bootstrap.Modal(document.getElementById('editOrderModal')).show();
}

function confirmDelete(orderId) {
    document.getElementById('delete_order_id').value = orderId;
    new bootstrap.Modal(document.getElementById('deleteOrderModal')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html> 