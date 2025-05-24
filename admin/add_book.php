<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "admin") {
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $pages = $_POST['pages'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    
    // Validate required fields
    if (empty($title) || empty($author) || empty($pages) || empty($price) || empty($stock)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("location: books.php");
        exit;
    }
    
    // Generate ISBN based on title and current timestamp
    $timestamp = time();
    $title_part = preg_replace('/[^a-zA-Z0-9]/', '', $title);
    $title_part = substr($title_part, 0, 5);
    $isbn = strtoupper($title_part) . date('ymd', $timestamp) . sprintf('%04d', rand(0, 9999));
    
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
                $cover_image = 'uploads/covers/' . $new_filename;
            }
        }
    }
    
    // Check if ISBN exists
    $sql = "SELECT id FROM books WHERE isbn = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $isbn);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $_SESSION['error'] = "A book with this ISBN already exists.";
        } else {
            // Insert new book
            $sql = "INSERT INTO books (isbn, title, author, category_id, pages, price, stock, cover_image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssiidis", $isbn, $title, $author, $category_id, $pages, $price, $stock, $cover_image);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = "Book added successfully with ISBN: " . $isbn;
                    header("location: books.php");
                    exit;
                } else {
                    $_SESSION['error'] = "Error adding book: " . mysqli_error($conn);
                }
            }
        }
    }
    
    header("location: books.php");
    exit;
}
?> 