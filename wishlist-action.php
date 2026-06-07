<?php
// wishlist-action.php - AJAX wishlist add/remove
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

    $action = $_POST['action'] ?? 'add';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $wishId = isset($_POST['wish_id']) ? (int)$_POST['wish_id'] : 0;

    if ($action === 'remove') {
        if ($wishId > 0) {
            $stmt = $pdo->prepare('DELETE FROM wishlist WHERE id = ? AND session_id = ?');
            $stmt->execute([$wishId, $_SESSION['session_id']]);
        } elseif ($productId > 0) {
            $stmt = $pdo->prepare('DELETE FROM wishlist WHERE product_id = ? AND session_id = ?');
            $stmt->execute([$productId, $_SESSION['session_id']]);
        } else {
            throw new Exception('Invalid wishlist item');
        }
        echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
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

    $stmt = $pdo->prepare('SELECT id FROM wishlist WHERE session_id = ? AND product_id = ?');
    $stmt->execute([$_SESSION['session_id'], $productId]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare('INSERT INTO wishlist (session_id, product_id) VALUES (?, ?)');
        $stmt->execute([$_SESSION['session_id'], $productId]);
    }

    echo json_encode(['success' => true, 'message' => $product['name'] . ' saved to wishlist']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
