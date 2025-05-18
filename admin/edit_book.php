<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "admin") {
    header("location: ../login.php");
    exit;
}

// Get book ID from URL
$book_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get all categories
$sql = "SELECT * FROM categories ORDER BY name";
$categories = mysqli_query($conn, $sql);
$categories_list = mysqli_fetch_all($categories, MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $isbn = trim($_POST['isbn']);
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $category_id = $_POST['category_id'];
    $pages = $_POST['pages'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    
    // Handle file upload
    $cover_image = $_POST['existing_cover'];
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['cover']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/covers/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $upload_path)) {
                // Delete old cover if exists
                if ($cover_image && file_exists('../' . $cover_image)) {
                    unlink('../' . $cover_image);
                }
                $cover_image = 'uploads/covers/' . $new_filename;
            }
        }
    }
    
    // Check if ISBN exists (excluding current book)
    $sql = "SELECT id FROM books WHERE isbn = ? AND id != ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $isbn, $book_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $_SESSION['error'] = "A book with this ISBN already exists.";
        } else {
            // Update book
            $sql = "UPDATE books SET isbn=?, title=?, author=?, category_id=?, pages=?, price=?, stock=?, cover_image=? WHERE id=?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssiidisi", $isbn, $title, $author, $category_id, $pages, $price, $stock, $cover_image, $book_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = "Book updated successfully.";
                    header("location: books.php");
                    exit;
                } else {
                    $_SESSION['error'] = "Something went wrong. Please try again later.";
                }
            }
        }
    }
    
    header("location: books.php");
    exit;
}

// Get book details
$sql = "SELECT * FROM books WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $book = mysqli_fetch_assoc($result);
    
    if (!$book) {
        header("location: books.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - Jungs Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

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
                            <a class="nav-link active text-white" href="books.php">
                                <i class="fas fa-book"></i> Manage Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="users.php">
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
                    <h1 class="h2">Edit Book</h1>
                    <a href="books.php" class="btn btn-secondary">Back to Books</a>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="author" class="form-label">Author</label>
                                <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-control" id="category" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories_list as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $book['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="pages" class="form-label">Number of Pages</label>
                                <input type="number" class="form-control" id="pages" name="pages" value="<?php echo $book['pages']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?php echo $book['price']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" value="<?php echo $book['stock']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="cover" class="form-label">Cover Image</label>
                                <?php if ($book['cover_image']): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo '../' . $book['cover_image']; ?>" alt="Current cover" class="img-thumbnail" style="max-width: 200px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="cover" name="cover" accept="image/*">
                                <input type="hidden" name="existing_cover" value="<?php echo $book['cover_image']; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Update Book</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html> 