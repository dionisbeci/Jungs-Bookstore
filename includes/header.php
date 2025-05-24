<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Jungs Bookstore</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="books.php">Books</a>
                </li>
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="library.php">My Library</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="wishlist.php">Wishlist</a>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/bookstore/admin/dashboard.php">Admin Dashboard</a>
                    </li>
                <?php endif; ?>
            </ul>
            <form class="d-flex me-3" action="books.php" method="get">
                <div class="input-group">
                    <input class="form-control" type="search" name="search" placeholder="Search books..." aria-label="Search">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span id="cartCount" class="badge bg-primary position-absolute" style="top: 50%; transform: translateY(-50%); right: -5px;">0</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'admin'): ?>
                            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar {
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}
.navbar-brand {
    font-weight: 600;
    font-size: 1.3rem;
}
.nav-link {
    font-weight: 500;
    padding: 0.5rem 1rem !important;
    transition: color 0.3s ease;
}
.nav-link:hover {
    color: #fff !important;
}
.dropdown-menu {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,.1);
}
.dropdown-item {
    padding: 0.5rem 1rem;
    transition: background-color 0.3s ease;
}
.dropdown-item:hover {
    background-color: #f8f9fa;
}
.input-group {
    width: 300px;
}
.input-group .form-control {
    border-right: none;
}
.input-group .btn {
    border-left: none;
}
#cartCount {
    font-size: 0.7rem;
    padding: 0.25em 0.4em;
    border-radius: 50%;
}
</style>

<?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'admin'): ?>
<script>
    // Initialize cart count on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
    });
</script>
<?php endif; ?> 