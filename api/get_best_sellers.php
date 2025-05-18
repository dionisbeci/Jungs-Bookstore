<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Get best sellers based on order quantities
$sql = "SELECT b.*, c.name as category_name, 
        COALESCE(SUM(oi.quantity), 0) as total_sold 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        LEFT JOIN order_items oi ON b.id = oi.book_id 
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed' 
        WHERE b.stock > 0 
        GROUP BY b.id 
        ORDER BY total_sold DESC, b.title ASC 
        LIMIT 8";

$result = mysqli_query($conn, $sql);
$books = [];

while ($book = mysqli_fetch_assoc($result)) {
    // Format the book data
    $books[] = [
        'id' => $book['id'],
        'title' => $book['title'],
        'author' => $book['author'],
        'price' => $book['price'],
        'cover_image' => $book['cover_image'],
        'category_name' => $book['category_name'],
        'stock' => $book['stock']
    ];
}

echo json_encode($books);
?> 