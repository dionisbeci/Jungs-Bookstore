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

// Handle book deletion
if (isset($_POST['delete_book'])) {
    $book_id = $_POST['book_id'];
    
    // Check if book has been ordered
    $sql = "SELECT COUNT(*) as order_count FROM order_items WHERE book_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['order_count'] > 0) {
            $error = "Cannot delete this book because it has been ordered. You can set the stock to 0 instead.";
        } else {
            // Get book image before deletion
            $sql = "SELECT cover_image FROM books WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $book_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $book = mysqli_fetch_assoc($result);
                
                // Delete the book
                $sql = "DELETE FROM books WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $book_id);
                    if (mysqli_stmt_execute($stmt)) {
                        // Delete the image file if it exists
                        if ($book && $book['cover_image'] && file_exists('../' . $book['cover_image'])) {
                            unlink('../' . $book['cover_image']);
                        }
                        $success = "Book deleted successfully.";
                    } else {
                        $error = "Error deleting book.";
                    }
                }
            }
        }
    }
}

// Handle book update
if (isset($_POST['update_book'])) {
    $book_id = $_POST['book_id'];
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    
    if (empty($title) || empty($author) || $price <= 0 || $stock < 0) {
        $error = "Please fill in all required fields correctly.";
    } else {
        // Handle file upload
        $cover_image = null;
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
                    // Get old cover image path
                    $sql = "SELECT cover_image FROM books WHERE id = ?";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $book_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $book = mysqli_fetch_assoc($result);
                        
                        // Delete old cover image if it exists
                        if ($book && $book['cover_image'] && file_exists('../' . $book['cover_image'])) {
                            unlink('../' . $book['cover_image']);
                        }
                    }
                    
                    $cover_image = 'uploads/covers/' . $new_filename;
                }
            }
        }
        
        // Update book
        if ($cover_image) {
            $sql = "UPDATE books SET title = ?, author = ?, price = ?, stock = ?, category_id = ?, cover_image = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssdiisi", $title, $author, $price, $stock, $category_id, $cover_image, $book_id);
            }
        } else {
            $sql = "UPDATE books SET title = ?, author = ?, price = ?, stock = ?, category_id = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssdiii", $title, $author, $price, $stock, $category_id, $book_id);
            }
        }
        
        if (isset($stmt) && mysqli_stmt_execute($stmt)) {
            $success = "Book updated successfully.";
        } else {
            $error = "Error updating book.";
        }
    }
}

// Get all books with category names
$sql = "SELECT b.*, c.name as category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        ORDER BY b.id DESC";
$books = mysqli_query($conn, $sql);

// Get all categories for the dropdown
$sql = "SELECT * FROM categories ORDER BY name";
$categories = mysqli_query($conn, $sql);
$categories_list = mysqli_fetch_all($categories, MYSQLI_ASSOC);
?>

    <div class="container-fluid">
        <div class="row">
        <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Books</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                        <i class="fas fa-plus"></i> Add New Book
                    </button>
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
                                    <th>ID</th>
                                    <th>Image</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($book = mysqli_fetch_assoc($books)): ?>
                                <tr>
                                        <td><?php echo $book['id']; ?></td>
                                    <td>
                                            <img src="<?php echo $book['cover_image'] ? '../' . $book['cover_image'] : '../assets/images/no-image.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>$<?php echo number_format($book['price'], 2); ?></td>
                                    <td><?php echo $book['stock']; ?></td>
                                    <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteBook(<?php echo $book['id']; ?>)">
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

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_book.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="author" class="form-label">Author</label>
                            <input type="text" class="form-control" id="author" name="author" required>
                        </div>
                        <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories_list as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="pages" class="form-label">Number of Pages</label>
                                <input type="number" class="form-control" id="pages" name="pages" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                        </div>
                        <div class="mb-3">
                                <label for="cover" class="form-label">Book Cover</label>
                            <input type="file" class="form-control" id="cover" name="cover" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Edit Book Modal -->
<div class="modal fade" id="editBookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="update_book" value="1">
                <input type="hidden" id="edit_book_id" name="book_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="edit_title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_author" class="form-label">Author</label>
                                <input type="text" class="form-control" id="edit_author" name="author" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_category_id" class="form-label">Category</label>
                                <select class="form-select" id="edit_category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories_list as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Price</label>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="edit_stock" name="stock" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_cover" class="form-label">Book Cover</label>
                                <input type="file" class="form-control" id="edit_cover" name="cover" accept="image/*">
                                <small class="text-muted">Leave empty to keep current cover</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <script>
function editBook(book) {
    document.getElementById('edit_book_id').value = book.id;
    document.getElementById('edit_title').value = book.title;
    document.getElementById('edit_author').value = book.author;
    document.getElementById('edit_category_id').value = book.category_id || '';
    document.getElementById('edit_price').value = book.price;
    document.getElementById('edit_stock').value = book.stock;
    new bootstrap.Modal(document.getElementById('editBookModal')).show();
}

function deleteBook(bookId) {
    if (confirm('WARNING: This will permanently delete the book and all its related records (orders, wishlist items, etc.). This action cannot be undone. Are you sure you want to proceed?')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = 'delete_book.php';
        form.innerHTML = `
            <input type="hidden" name="book_id" value="${bookId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
    </script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html> 