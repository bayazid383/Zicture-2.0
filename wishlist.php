<?php
// wishlist.php - Wishlist management page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

try {
    $stmt = $pdo->prepare('SELECT w.id as wish_id, p.id, p.name, p.price, p.image, p.rating, p.description FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.session_id = ? ORDER BY w.added_at DESC');
    $stmt->execute([$_SESSION['session_id']]);
    $wishlistItems = $stmt->fetchAll();
} catch (Exception $e) {
    $wishlistItems = [];
}

$title = 'Wishlist - Zicture';
include 'header-include.php';
?>

<main class="container my-5">
    <div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-4">
        <div>
            <h1 class="display-6 fw-bold mb-2"><i class="fa-solid fa-heart text-danger me-2"></i>My Wishlist</h1>
            <p class="text-muted mb-0">Products saved for later shopping.</p>
        </div>
        <a href="products.php?category=all" class="btn btn-primary">Browse Products</a>
    </div>

    <?php if (empty($wishlistItems)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="fa-solid fa-heart-crack fa-3x mb-3"></i>
            <h4>Your wishlist is empty</h4>
            <p>Add favorite products from the homepage or product page.</p>
            <a href="products.php?category=all" class="btn btn-primary">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($wishlistItems as $item): ?>
                <div class="col-md-6 col-xl-3">
                    <div class="product-card h-100 d-flex flex-column">
                        <div class="product-image-wrap">
                            <img src="./photo/<?php echo htmlspecialchars($item['image']); ?>" class="product-image" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='./photo/Z_Energy_logo.png'">
                        </div>
                        <div class="card-body d-flex flex-column p-3">
                            <h6 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <div class="product-rating mb-2">
                                <?php for ($i = 0; $i < (int)$item['rating']; $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                                <?php for ($i = (int)$item['rating']; $i < 5; $i++): ?><i class="fa-regular fa-star"></i><?php endfor; ?>
                            </div>
                            <p class="text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 80)); ?>...</p>
                            <h5 class="product-price mb-3"><?php echo zicture_money((float)$item['price']); ?></h5>
                            <div class="product-actions">
                                <button class="btn btn-buy btn-sm wide" onclick="zictureAddToCart(<?php echo (int)$item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>')">Add to Cart</button>
                                <button class="btn btn-success btn-sm" onclick="zictureBuyNow(<?php echo (int)$item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>')">Buy Now</button>
                                <button class="btn btn-outline-primary btn-sm" onclick="zictureCompare(<?php echo (int)$item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>')">Compare</button>
                                <button class="btn btn-outline-danger btn-sm wide" onclick="zictureWishlist(<?php echo (int)$item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>', 'remove', <?php echo (int)$item['wish_id']; ?>)">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer-include.php'; ?>
