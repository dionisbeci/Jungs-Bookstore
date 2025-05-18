<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get cart items
        $sql = "SELECT c.*, b.price, b.stock FROM cart c 
                JOIN books b ON c.book_id = b.id 
                WHERE c.user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $cart_items = mysqli_stmt_get_result($stmt);
        
        $total_amount = 0;
        $items = [];
        
        // Check stock and calculate total
        while ($item = mysqli_fetch_assoc($cart_items)) {
            if ($item['quantity'] > $item['stock']) {
                throw new Exception("Not enough stock for some items");
            }
            $total_amount += $item['price'] * $item['quantity'];
            $items[] = $item;
        }
        
        // Create order
        $sql = "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "id", $_SESSION['user_id'], $total_amount);
        mysqli_stmt_execute($stmt);
        $order_id = mysqli_insert_id($conn);
        
        // Add order items and update stock
        foreach ($items as $item) {
            // Add order item
            $sql = "INSERT INTO order_items (order_id, book_id, quantity, price_at_time) 
                    VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiid", $order_id, $item['book_id'], $item['quantity'], $item['price']);
            mysqli_stmt_execute($stmt);
            
            // Update stock
            $sql = "UPDATE books SET stock = stock - ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $item['quantity'], $item['book_id']);
            mysqli_stmt_execute($stmt);
        }
        
        // Clear cart
        $sql = "DELETE FROM cart WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Redirect to success page
        $_SESSION['success'] = "Order placed successfully!";
        header("location: orders.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get cart items for display
$sql = "SELECT c.*, b.title, b.author, b.price, b.cover_image 
        FROM cart c 
        JOIN books b ON c.book_id = b.id 
        WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$cart_items = mysqli_stmt_get_result($stmt);

// Calculate total
$total = 0;
$cart_items_array = [];
while ($item = mysqli_fetch_assoc($cart_items)) {
    $item['subtotal'] = $item['quantity'] * $item['price'];
    $total += $item['subtotal'];
    $cart_items_array[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Jungs Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Checkout</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items_array)): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="books.php" class="alert-link">Continue shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($cart_items_array as $item): ?>
                                <div class="row mb-4">
                                    <div class="col-md-2">
                                        <img src="<?php echo $item['cover_image'] ? $item['cover_image'] : 'assets/images/default-book-cover.jpg'; ?>" 
                                             class="img-fluid" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                                        <p class="text-muted">By <?php echo htmlspecialchars($item['author']); ?></p>
                                        <p>Quantity: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <p class="mb-0">Price: $<?php echo number_format($item['price'], 2); ?></p>
                                        <p class="text-primary">Subtotal: $<?php echo number_format($item['subtotal'], 2); ?></p>
                                    </div>
                                </div>
                                <?php if (!($item === end($cart_items_array))): ?>
                                    <hr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Shipping</span>
                                <span>Free</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total</strong>
                                <strong>$<?php echo number_format($total, 2); ?></strong>
                            </div>
                            <form method="post" action="">
                                <button type="submit" class="btn btn-primary w-100">
                                    Place Order
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 