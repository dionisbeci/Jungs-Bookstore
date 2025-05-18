<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

// Handle quantity updates
if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = max(1, intval($_POST['quantity']));
    
    $sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iii", $quantity, $cart_id, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
    }
}

// Handle item removal
if (isset($_POST['remove_item'])) {
    $cart_id = $_POST['cart_id'];
    
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $cart_id, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
    }
}

// Get cart items
$sql = "SELECT c.*, b.title, b.author, b.price, b.cover_image, b.stock 
        FROM cart c 
        JOIN books b ON c.book_id = b.id 
        WHERE c.user_id = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $cart_items = mysqli_stmt_get_result($stmt);
}

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
    <title>Shopping Cart - Jungs Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Shopping Cart</h2>

        <?php if (!empty($cart_items_array)): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
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
                                        <p class="text-primary">$<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <form action="" method="post" class="mb-2">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <div class="input-group">
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                       min="1" max="<?php echo $item['stock']; ?>" class="form-control">
                                                <button type="submit" name="update_quantity" class="btn btn-outline-secondary">
                                                    Update
                                                </button>
                                            </div>
                                        </form>
                                        <form action="" method="post">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="remove_item" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </form>
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
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Shipping</span>
                                <span>Free</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total</strong>
                                <strong>$<?php echo number_format($total, 2); ?></strong>
                            </div>
                            <a href="checkout.php" class="btn btn-primary w-100">
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                Your cart is empty. 
                <a href="books.php" class="alert-link">Continue shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 