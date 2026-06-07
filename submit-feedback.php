<?php
// submit-feedback.php - simple handler for feedback form
// Place this project folder under XAMPP's htdocs (e.g., C:\xampp\htdocs\Zicture)
// SQL to create DB/table (run once in phpMyAdmin or mysql CLI):
/*
CREATE DATABASE IF NOT EXISTS zicture CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE zicture;
CREATE TABLE IF NOT EXISTS feedbacks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fname VARCHAR(100),
  lname VARCHAR(100),
  email VARCHAR(255),
  password VARCHAR(255),
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$message = trim($_POST['message'] ?? '');

// Basic validation
if (!$fname || !$email) {
    header('Location: index.php?error=missing');
    exit;
}

// Hash password before storing
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO feedbacks (fname, lname, email, password, message) VALUES (:fname, :lname, :email, :password, :message)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':fname' => $fname,
        ':lname' => $lname,
        ':email' => $email,
        ':password' => $passwordHash,
        ':message' => $message,
    ]);
} catch (Exception $e) {
    header('Location: index.php?error=server');
    exit;
}

header('Location: index.php?sent=1');
exit;
