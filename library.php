<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

// Handle hide/show book
if (isset($_POST['toggle_visibility'])) {
    $order_item_id = intval($_POST['order_item_id']);
    $visibility = $_POST['visibility'] === 'show' ? 0 : 1; // 0 = visible, 1 = hidden
    
    $sql = "UPDATE order_items SET is_hidden = ? WHERE id = ? AND EXISTS (
            SELECT 1 FROM orders WHERE orders.id = order_items.order_id 
            AND orders.user_id = ? AND orders.status = 'completed'
        )";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iii", $visibility, $order_item_id, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $_SESSION['success'] = $visibility ? "Book hidden from library" : "Book restored to library";
        }
    }
    
    // Redirect to remove POST data
    header("Location: library.php");
    exit;
}

// Get user's purchased books
$sql = "SELECT DISTINCT b.*, oi.order_id, oi.id as order_item_id, oi.is_hidden, o.order_date 
        FROM books b 
        JOIN order_items oi ON b.id = oi.book_id 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.user_id = ? AND o.status = 'completed'
        ORDER BY o.order_date DESC";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $books = mysqli_stmt_get_result($stmt);
}

// Count total and hidden books
$total_books = 0;
$hidden_books = 0;
$visible_books = [];
$hidden_book_list = [];

while ($book = mysqli_fetch_assoc($books)) {
    $total_books++;
    if ($book['is_hidden']) {
        $hidden_books++;
        $hidden_book_list[] = $book;
    } else {
        $visible_books[] = $book;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Library - Jungs Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Library</h2>
            <?php if ($hidden_books > 0): ?>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#hiddenBooksModal">
                    Show Hidden Books (<?php echo $hidden_books; ?>)
                </button>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($total_books > 0): ?>
            <div class="row">
                <?php foreach ($visible_books as $book): ?>
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
                                    <small class="text-muted">Purchased on <?php echo date('M d, Y', strtotime($book['order_date'])); ?></small>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="#" class="btn btn-primary" onclick="readBook(<?php echo $book['id']; ?>)">Read Book</a>
                                        <a href="#" class="btn btn-outline-primary" onclick="downloadBook(<?php echo $book['id']; ?>)">Download</a>
                                    </div>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="order_item_id" value="<?php echo $book['order_item_id']; ?>">
                                        <input type="hidden" name="visibility" value="hide">
                                        <button type="submit" name="toggle_visibility" class="btn btn-outline-secondary" title="Hide from library">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You haven't purchased any books yet. 
                <a href="books.php" class="alert-link">Browse our collection</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Hidden Books Modal -->
    <?php if ($hidden_books > 0): ?>
    <div class="modal fade" id="hiddenBooksModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hidden Books</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <?php foreach ($hidden_book_list as $book): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <img src="<?php echo $book['cover_image'] ? $book['cover_image'] : 'assets/images/default-book-cover.jpg'; ?>" 
                                                 class="img-fluid rounded-start" 
                                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                 style="height: 150px; object-fit: cover;">
                                        </div>
                                        <div class="col-8">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h6>
                                                <p class="card-text"><small>By <?php echo htmlspecialchars($book['author']); ?></small></p>
                                                <form method="post">
                                                    <input type="hidden" name="order_item_id" value="<?php echo $book['order_item_id']; ?>">
                                                    <input type="hidden" name="visibility" value="show">
                                                    <button type="submit" name="toggle_visibility" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Show in Library
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function readBook(bookId) {
            // Implement book reading functionality
            alert('Reading functionality will be implemented soon!');
        }

        function downloadBook(bookId) {
            // Implement book download functionality
            alert('Download functionality will be implemented soon!');
        }
    </script>
</body>
</html> 