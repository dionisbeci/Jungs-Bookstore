<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to wishlist']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$book_id = $data['book_id'] ?? null;

if (!$book_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

// Check if book exists
$sql = "SELECT id FROM books WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }
}

// Check if book is already in wishlist
$sql = "SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Remove from wishlist
        $sql = "DELETE FROM wishlist WHERE user_id = ? AND book_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $book_id);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Book removed from wishlist']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error removing book from wishlist']);
            }
        }
    } else {
        // Add to wishlist
        $sql = "INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $book_id);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Book added to wishlist']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding book to wishlist']);
            }
        }
    }
}
?> 