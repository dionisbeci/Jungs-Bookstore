<?php
session_start();
require_once 'config/database.php';

// Get search query and category if any
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Get all books with their categories
$sql = "SELECT b.*, c.name as category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        WHERE 1=1";

// Add category filter if specified
if ($category_id > 0) {
    $sql .= " AND b.category_id = ?";
}

// Add search condition if search query exists
if (!empty($search)) {
    $search = "%$search%";
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
}

$sql .= " ORDER BY b.title";

$stmt = mysqli_prepare($conn, $sql);

// Bind parameters
if ($category_id > 0 && !empty($search)) {
    mysqli_stmt_bind_param($stmt, "isss", $category_id, $search, $search, $search);
} elseif ($category_id > 0) {
    mysqli_stmt_bind_param($stmt, "i", $category_id);
} elseif (!empty($search)) {
    mysqli_stmt_bind_param($stmt, "sss", $search, $search, $search);
}

mysqli_stmt_execute($stmt);
$books = mysqli_stmt_get_result($stmt);

// Get wishlist status for each book if user is logged in
$wishlist_books = array();
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT book_id FROM wishlist WHERE user_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $wishlist_books[] = $row['book_id'];
        }
    }
}

// Get all categories for filter
$sql = "SELECT * FROM categories ORDER BY name";
$categories = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books - Jungs Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Filters -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Categories</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="books.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" 
                               class="list-group-item list-group-item-action <?php echo $category_id === 0 ? 'active' : ''; ?>">
                                All Categories
                            </a>
                            <?php 
                            // Reset categories result pointer
                            mysqli_data_seek($categories, 0);
                            while ($category = mysqli_fetch_assoc($categories)): 
                                $url = 'books.php?category=' . $category['id'];
                                if (!empty($search)) {
                                    $url .= '&search=' . urlencode($search);
                                }
                            ?>
                                <a href="<?php echo $url; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $category_id === intval($category['id']) ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Books Grid -->
            <div class="col-md-9">
                <?php if (!empty($search)): ?>
                    <h4 class="mb-4">Search Results for "<?php echo htmlspecialchars($search); ?>"</h4>
                <?php endif; ?>

                <?php if (mysqli_num_rows($books) === 0): ?>
                    <div class="alert alert-info">
                        No books found. <?php if ($category_id > 0 || !empty($search)): ?>Try adjusting your filters.<?php endif; ?>
                    </div>
                <?php else: ?>
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
                                            <small class="text-muted"><?php echo htmlspecialchars($book['category_name']); ?></small>
                                        </p>
                                        <p class="card-text">
                                            <strong>$<?php echo number_format($book['price'], 2); ?></strong>
                                        </p>
                                        <?php if ($book['stock'] > 0): ?>
                                            <button onclick="addToCart(<?php echo $book['id']; ?>)" class="btn btn-primary">
                                                Add to Cart
                                            </button>
                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <button class="btn btn-outline-secondary wishlist-btn" 
                                                        data-book-id="<?php echo $book['id']; ?>">
                                                    <i class="fas fa-heart <?php echo in_array($book['id'], $wishlist_books) ? 'text-danger' : ''; ?>"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" disabled>Out of Stock</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Define isLoggedIn variable for JavaScript
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html> 