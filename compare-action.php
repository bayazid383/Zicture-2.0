<?php
// compare-action.php - AJAX compare session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['compare_products'])) {
    $_SESSION['compare_products'] = [];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $action = $_POST['action'] ?? 'add';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($action === 'clear') {
        $_SESSION['compare_products'] = [];
        echo json_encode(['success' => true, 'message' => 'Comparison cleared', 'count' => 0]);
        exit;
    }

    if ($action === 'remove') {
        $_SESSION['compare_products'] = array_values(array_filter($_SESSION['compare_products'], function ($id) use ($productId) {
            return (int)$id !== $productId;
        }));
        echo json_encode(['success' => true, 'message' => 'Removed from comparison', 'count' => count($_SESSION['compare_products'])]);
        exit;
    }

    if ($productId <= 0) {
        throw new Exception('Invalid product');
    }

    $stmt = $pdo->prepare('SELECT id, name FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        throw new Exception('Product not found');
    }

    if (!in_array($productId, $_SESSION['compare_products'], true)) {
        if (count($_SESSION['compare_products']) >= 4) {
            throw new Exception('You can compare up to 4 products');
        }
        $_SESSION['compare_products'][] = $productId;
    }

    echo json_encode(['success' => true, 'message' => $product['name'] . ' added to compare', 'count' => count($_SESSION['compare_products'])]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'count' => count($_SESSION['compare_products'] ?? [])]);
}
?>
