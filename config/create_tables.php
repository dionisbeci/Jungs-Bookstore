<?php
require_once 'database.php';

// Users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    surname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'user') DEFAULT 'user',
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    echo "Error creating users table: " . mysqli_error($conn);
}

// Books table
$sql = "CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    isbn VARCHAR(13) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    category_id INT,
    pages INT NOT NULL,
    cover_image VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    echo "Error creating books table: " . mysqli_error($conn);
}

// Categories table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    echo "Error creating categories table: " . mysqli_error($conn);
}

// Orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    echo "Error creating orders table: " . mysqli_error($conn);
}

// Order items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_time DECIMAL(10,2) NOT NULL
)";

if (!mysqli_query($conn, $sql)) {
    echo "Error creating order_items table: " . mysqli_error($conn);
}

// Wishlist table
$sql = "CREATE TABLE IF NOT EXISTS wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    echo "Error creating wishlist table: " . mysqli_error($conn);
}

// Reviews table
$sql = "CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    echo "Error creating reviews table: " . mysqli_error($conn);
}

// Create cart table
$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
)";

if (!mysqli_query($conn, $sql)) {
    echo "Error creating cart table: " . mysqli_error($conn) . "\n";
}

// Add foreign key constraints
$constraints = [
    "ALTER TABLE books ADD FOREIGN KEY (category_id) REFERENCES categories(id)",
    "ALTER TABLE orders ADD FOREIGN KEY (user_id) REFERENCES users(id)",
    "ALTER TABLE order_items ADD FOREIGN KEY (order_id) REFERENCES orders(id)",
    "ALTER TABLE order_items ADD FOREIGN KEY (book_id) REFERENCES books(id)",
    "ALTER TABLE wishlist ADD FOREIGN KEY (user_id) REFERENCES users(id)",
    "ALTER TABLE wishlist ADD FOREIGN KEY (book_id) REFERENCES books(id)",
    "ALTER TABLE reviews ADD FOREIGN KEY (user_id) REFERENCES users(id)",
    "ALTER TABLE reviews ADD FOREIGN KEY (book_id) REFERENCES books(id)"
];

foreach ($constraints as $constraint) {
    mysqli_query($conn, $constraint);
}

// Create default admin user
$default_admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, name, surname, email, password, user_type) 
        VALUES ('admin', 'System', 'Administrator', 'admin@jungsbookstore.com', '$default_admin_password', 'admin')";

if (!mysqli_query($conn, $sql)) {
    echo "Error creating default admin user: " . mysqli_error($conn);
}

echo "Database setup completed successfully!";
?> 