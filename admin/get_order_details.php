<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "admin") {
    exit('Unauthorized access');
}

if (!isset($_GET['id'])) {
    exit('Order ID is required');
}

$order_id = $_GET['id'];

// Get order details
$sql = "SELECT o.*, u.username, u.email, u.name, u.surname 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    
    if ($order) {
        // Get order items with price from order_items table
        $sql = "SELECT oi.*, oi.price_at_time as item_price, b.title, b.author, b.cover_image 
                FROM order_items oi 
                JOIN books b ON oi.book_id = b.id 
                WHERE oi.order_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            $items_result = mysqli_stmt_get_result($stmt);
            $items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);
            
            // Output order details
            ?>
            <div class="order-details">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                        <p><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php 
                                echo $order['status'] === 'completed' ? 'success' : 
                                    ($order['status'] === 'pending' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </p>
                        <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['name'] . ' ' . $order['surname']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    </div>
                </div>
                
                <h6>Order Items</h6>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $item['cover_image'] ? '../' . $item['cover_image'] : '../assets/images/no-image.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td><?php echo htmlspecialchars($item['author']); ?></td>
                                    <td>$<?php echo number_format($item['item_price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['item_price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<div class="alert alert-danger">Order not found.</div>';
    }
} else {
    echo '<div class="alert alert-danger">Error retrieving order details.</div>';
}
?> 