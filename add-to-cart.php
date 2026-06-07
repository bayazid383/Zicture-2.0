<?php
// add-to-cart.php - Handle adding products to cart
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity < 1) {
        $quantity = 1;
    }

    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }

    $stmt = $pdo->prepare('SELECT id, name, price, image, stock FROM products WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) {
        throw new Exception('Product not found');
    }

    $stmt = $pdo->prepare('SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?');
    $stmt->execute([$_SESSION['session_id'], $product_id]);
    $existingItem = $stmt->fetch();

    if ($existingItem) {
        $newQuantity = min(100, (int)$existingItem['quantity'] + $quantity);
        $stmt = $pdo->prepare('UPDATE cart SET quantity = ? WHERE id = ? AND session_id = ?');
        $stmt->execute([$newQuantity, $existingItem['id'], $_SESSION['session_id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)');
        $stmt->execute([$_SESSION['session_id'], $product_id, $quantity]);
    }

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE session_id = ?');
    $stmt->execute([$_SESSION['session_id']]);
    $result = $stmt->fetch();
    $cartCount = (int)($result['total'] ?? 0);

    echo json_encode([
        'success' => true,
        'message' => $product['name'] . ' added to cart',
        'cartCount' => $cartCount,
        'product' => [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => (float)$product['price'],
            'image' => $product['image']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
