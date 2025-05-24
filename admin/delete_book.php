<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "admin") {
    header("location: ../login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Delete from wishlist
        $sql = "DELETE FROM wishlist WHERE book_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        mysqli_stmt_execute($stmt);
        
        // 2. Delete from order_items
        $sql = "DELETE FROM order_items WHERE book_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        mysqli_stmt_execute($stmt);
        
        // 3. Get book image before deletion
        $sql = "SELECT cover_image FROM books WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $book = mysqli_fetch_assoc($result);
        
        // 4. Delete the book
        $sql = "DELETE FROM books WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        mysqli_stmt_execute($stmt);
        
        // 5. Delete the image file if it exists
        if ($book && $book['cover_image'] && file_exists('../' . $book['cover_image'])) {
            unlink('../' . $book['cover_image']);
        }
        
        // If everything went well, commit the transaction
        mysqli_commit($conn);
        $success = "Book and all related records have been successfully deleted.";
        
    } catch (Exception $e) {
        // If there was an error, rollback the transaction
        mysqli_rollback($conn);
        $error = "Error deleting book: " . $e->getMessage();
    }
}

// Redirect back to books page
$_SESSION['error'] = $error;
$_SESSION['success'] = $success;
header("location: books.php");
exit; 