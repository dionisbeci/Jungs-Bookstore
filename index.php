<?php
session_start();
require_once 'config/database.php';

// Check if the tables need to be created
$sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    // Database doesn't exist, create it
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if (mysqli_query($conn, $sql)) {
        mysqli_select_db($conn, DB_NAME);
        require_once 'config/create_tables.php';
    }
} else {
    mysqli_select_db($conn, DB_NAME);
    // Check if tables exist
    $sql = "SHOW TABLES LIKE 'users'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) == 0) {
        // Tables don't exist, create them
        require_once 'config/create_tables.php';
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Get featured categories
$sql = "SELECT c.*, COUNT(b.id) as book_count 
        FROM categories c 
        LEFT JOIN books b ON c.id = b.category_id 
        GROUP BY c.id 
        ORDER BY book_count DESC 
        LIMIT 4";
$featured_categories = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jungs Bookstore - Your Online Book Haven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('uploads/images/Zhongshuge-Bookstore-X-Living.gif');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            padding: 100px 0;
            margin-bottom: 40px;
            position: relative;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }
        .hero-section .container {
            position: relative;
            z-index: 2;
        }
        .book-card {
            transition: transform 0.3s ease;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .category-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .section-title {
            position: relative;
            margin-bottom: 40px;
            padding-bottom: 15px;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: #007bff;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Welcome to Jungs Bookstore</h1>
            <p class="lead mb-4">Discover your next favorite book from our vast collection</p>
            <a href="books.php" class="btn btn-primary btn-lg">Browse Books</a>
        </div>
    </section>

    <!-- Best Sellers Section -->
    <section class="container mb-5">
        <h2 class="section-title">Best Sellers</h2>
        <div class="row" id="bestSellers">
            <!-- Best sellers will be loaded here via JavaScript -->
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Categories -->
    <section class="container mb-5">
        <h2 class="section-title">Featured Categories</h2>
        <div class="row">
            <?php while ($category = mysqli_fetch_assoc($featured_categories)): ?>
                <div class="col-md-3 mb-4">
                    <div class="category-card">
                        <i class="fas fa-book-open fa-3x mb-3 text-primary"></i>
                        <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                        <p class="text-muted"><?php echo $category['book_count']; ?> Books</p>
                        <a href="books.php?category=<?php echo $category['id']; ?>" class="btn btn-outline-primary">
                            Browse Category
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Define isLoggedIn variable for JavaScript
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html> 