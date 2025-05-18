<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$book_id = $data['book_id'] ?? null;

if (!$book_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

// Check if book exists and is in stock
$sql = "SELECT id, stock FROM books WHERE id = ? AND stock > 0";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Book not available']);
        exit;
    }
}

// Check if book is already in cart
$sql = "SELECT quantity FROM cart WHERE user_id = ? AND book_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Update quantity
        $sql = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND book_id = ?";
    } else {
        // Add new item
        $sql = "INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, 1)";
    }
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $book_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Book added to cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding book to cart']);
        }
    }
}
?> 