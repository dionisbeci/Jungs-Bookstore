<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get order details
$sql = "SELECT o.* FROM orders o 
        WHERE o.id = ? AND o.user_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    
    if (!$order) {
        header("location: orders.php");
        exit;
    }
}

// Get order items
$sql = "SELECT oi.*, b.title, b.author, b.isbn, b.cover_image 
        FROM order_items oi 
        JOIN books b ON oi.book_id = b.id 
        WHERE oi.order_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $order_items = mysqli_stmt_get_result($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - Jungs Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Order #<?php echo $order_id; ?></h2>
            <a href="orders.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <?php while ($item = mysqli_fetch_assoc($order_items)): ?>
                            <div class="row mb-4">
                                <div class="col-md-2">
                                    <img src="<?php echo $item['cover_image'] ? $item['cover_image'] : 'assets/images/default-book-cover.jpg'; ?>" 
                                         class="img-fluid" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                                    <p class="text-muted">By <?php echo htmlspecialchars($item['author']); ?></p>
                                    <p class="mb-0">ISBN: <?php echo htmlspecialchars($item['isbn']); ?></p>
                                    <p>Quantity: <?php echo $item['quantity']; ?></p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <p class="mb-0">Price: $<?php echo number_format($item['price_at_time'], 2); ?></p>
                                    <p class="text-primary">
                                        Subtotal: $<?php echo number_format($item['quantity'] * $item['price_at_time'], 2); ?>
                                    </p>
                                </div>
                            </div>
                            <?php if ($item !== mysqli_fetch_assoc($order_items)): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Order Date</span>
                            <span><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Status</span>
                            <?php
                            $status_class = [
                                'pending' => 'warning',
                                'completed' => 'success',
                                'rejected' => 'danger'
                            ];
                            ?>
                            <span class="badge bg-<?php echo $status_class[$order['status']]; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Total Amount</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <?php if ($order['status'] === 'completed'): ?>
                            <div class="alert alert-success mb-0">
                                <i class="fas fa-check-circle"></i> Your order has been completed.
                                <?php if (isset($order['tracking_number'])): ?>
                                    <br>
                                    Tracking Number: <?php echo htmlspecialchars($order['tracking_number']); ?>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($order['status'] === 'rejected'): ?>
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-times-circle"></i> Your order has been rejected.
                                <?php if (isset($order['rejection_reason'])): ?>
                                    <br>
                                    Reason: <?php echo htmlspecialchars($order['rejection_reason']); ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-clock"></i> Your order is being processed.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 