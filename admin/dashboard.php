<?php
session_start();
require_once '../config/database.php';

// Include the admin header
include 'includes/header.php';

// Get statistics
$stats = array();

// Total users
$sql = "SELECT COUNT(*) as total FROM users WHERE user_type = 'user'";
$result = mysqli_query($conn, $sql);
$stats['total_users'] = mysqli_fetch_assoc($result)['total'];

// Total books
$sql = "SELECT COUNT(*) as total FROM books";
$result = mysqli_query($conn, $sql);
$stats['total_books'] = mysqli_fetch_assoc($result)['total'];

// Total orders
$sql = "SELECT COUNT(*) as total FROM orders";
$result = mysqli_query($conn, $sql);
$stats['total_orders'] = mysqli_fetch_assoc($result)['total'];

// Recent orders
$sql = "SELECT o.*, u.username FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.order_date DESC LIMIT 5";
$recent_orders = mysqli_query($conn, $sql);

// Best selling books
$sql = "SELECT b.*, COUNT(oi.book_id) as sales_count 
        FROM books b 
        JOIN order_items oi ON b.id = oi.book_id 
        GROUP BY b.id 
        ORDER BY sales_count DESC 
        LIMIT 5";
$best_sellers = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Jungs Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .quick-access-btn {
            transition: transform 0.2s;
            height: 100%;
        }
        .quick-access-btn:hover {
            transform: translateY(-5px);
        }
        .quick-access-btn i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Main content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Quick Access Buttons -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <a href="books.php" class="btn btn-primary w-100 quick-access-btn d-flex flex-column align-items-center justify-content-center p-4">
                            <i class="fas fa-plus-circle"></i>
                            <span>Add New Book</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="categories.php" class="btn btn-success w-100 quick-access-btn d-flex flex-column align-items-center justify-content-center p-4">
                            <i class="fas fa-folder-plus"></i>
                            <span>Add Category</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="users.php" class="btn btn-info w-100 quick-access-btn d-flex flex-column align-items-center justify-content-center p-4">
                            <i class="fas fa-user-cog"></i>
                            <span>Manage Users</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="orders.php" class="btn btn-warning w-100 quick-access-btn d-flex flex-column align-items-center justify-content-center p-4">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Manage Orders</span>
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase">Total Users</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_users']; ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase">Total Books</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_books']; ?></h2>
                                    </div>
                                    <i class="fas fa-book fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase">Total Orders</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_orders']; ?></h2>
                                    </div>
                                    <i class="fas fa-shopping-cart fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $order['status'] === 'completed' ? 'success' : 
                                                        ($order['status'] === 'pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Best Selling Books -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Best Selling Books</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Book</th>
                                        <th>Author</th>
                                        <th>Price</th>
                                        <th>Sales Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($book = mysqli_fetch_assoc($best_sellers)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                                            <td>$<?php echo number_format($book['price'], 2); ?></td>
                                            <td><?php echo $book['sales_count']; ?></td>
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

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
    function viewOrder(orderId) {
        // Show loading state
        document.getElementById('orderDetails').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        // Show the modal
        new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
        
        // Fetch order details
        fetch('get_order_details.php?id=' + orderId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('orderDetails').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('orderDetails').innerHTML = '<div class="alert alert-danger">Error loading order details.</div>';
            });
    }
    </script>
</body>
</html> 