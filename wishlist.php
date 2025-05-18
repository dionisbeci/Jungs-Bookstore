<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

// Handle wishlist item removal
if (isset($_POST['remove_from_wishlist'])) {
    $book_id = $_POST['book_id'];
    $sql = "DELETE FROM wishlist WHERE user_id = ? AND book_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $book_id);
        mysqli_stmt_execute($stmt);
    }
}

// Get user's wishlist items
$sql = "SELECT b.*, w.added_date 
        FROM books b 
        JOIN wishlist w ON b.id = w.book_id 
        WHERE w.user_id = ? 
        ORDER BY w.added_date DESC";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $books = mysqli_stmt_get_result($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Jungs Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">My Wishlist</h2>

        <?php if (mysqli_num_rows($books) > 0): ?>
            <div class="row">
                <?php while ($book = mysqli_fetch_assoc($books)): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo $book['cover_image'] ? $book['cover_image'] : 'assets/images/default-book-cover.jpg'; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 style="height: 300px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                <p class="card-text">By <?php echo htmlspecialchars($book['author']); ?></p>
                                <p class="card-text">
                                    <strong>$<?php echo number_format($book['price'], 2); ?></strong>
                                </p>
                                <p class="card-text">
                                    <small class="text-muted">Added on <?php echo date('M d, Y', strtotime($book['added_date'])); ?></small>
                                </p>
                                <?php if ($book['stock'] > 0): ?>
                                    <button onclick="addToCart(<?php echo $book['id']; ?>)" class="btn btn-primary">
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Out of Stock</button>
                                <?php endif; ?>
                                <form action="" method="post" class="d-inline">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" name="remove_from_wishlist" class="btn btn-outline-danger">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                Your wishlist is empty. 
                <a href="books.php" class="alert-link">Browse our collection</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 