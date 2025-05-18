<?php
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = 'user';
    $profile_picture = 'default.jpg'; // For now

    $stmt = $pdo->prepare("INSERT INTO users (username, name, surname, email, password, user_type, profile_picture)
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $name, $surname, $email, $password, $user_type, $profile_picture]);

    $_SESSION['user'] = $username;
    header("Location: ../user/dashboard.php");
    exit;
}
?>

<form method="POST" action="">
    <h2>Sign Up</h2>
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="text" name="name" placeholder="Name" required><br>
    <input type="text" name="surname" placeholder="Surname" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>
    