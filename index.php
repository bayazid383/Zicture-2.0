<?php
// index.php - Responsive homepage with working sections
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

$alert = '';
if (isset($_GET['sent'])) {
    $alert = '<div class="alert alert-success text-center mt-3">Thanks - your message was received.</div>';
} elseif (isset($_GET['error'])) {
    $alert = '<div class="alert alert-danger text-center mt-3">Error: ' . htmlspecialchars($_GET['error']) . '</div>';
}

try {
    $productColumns = [];
    foreach ($pdo->query('SHOW COLUMNS FROM products')->fetchAll() as $column) {
        $productColumns[$column['Field']] = true;
    }
    $stmt = $pdo->query('SELECT * FROM products ORDER BY created_at DESC, id DESC LIMIT 12');
    $products = $stmt->fetchAll();
    $dailyOrder = isset($productColumns['is_daily']) ? 'is_daily DESC, ' : '';
    $upcomingOrder = isset($productColumns['is_upcoming']) ? 'is_upcoming DESC, ' : '';
    $hotStmt = $pdo->query('SELECT * FROM products ORDER BY ' . $dailyOrder . 'rating DESC, price ASC LIMIT 4');
    $hotProducts = $hotStmt->fetchAll();
    $upcomingStmt = $pdo->query('SELECT * FROM products ORDER BY ' . $upcomingOrder . 'created_at DESC, id DESC LIMIT 6');
    $upcomingProducts = $upcomingStmt->fetchAll();
} catch (Exception $e) {
    $products = [];
    $hotProducts = [];
    $upcomingProducts = [];
}

$title = 'Zicture - The Online Shopping';
include 'header-include.php';

$categories = zicture_categories();
unset($categories['all']);
?>

<main>
    <section class="hero-section">
        <div class="container">
            <?php echo $alert; ?>
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <p class="text-primary fw-bold mb-2">Hot deals, easy cart, fast delivery</p>
                    <h1 class="hero-title mb-3">Zicture - The Online Shopping</h1>
                    <p class="hero-copy lead mb-4">Shop daily essentials, books, fashion, games, medicine, electronics, software, and more with working search, cart, wishlist, compare, coupon, support, and invoice download.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="products.php?category=all" class="btn btn-primary btn-lg"><i class="fa-solid fa-bag-shopping me-2"></i>Shop Now</a>
                        <a href="#upcoming" class="btn btn-soft btn-lg"><i class="fa-solid fa-clock me-2"></i>Upcoming</a>
                        <button type="button" class="btn btn-outline-dark btn-lg" onclick="copyCoupon('ZICTURE20')"><i class="fa-solid fa-tags me-2"></i>Coupon Discount</button>
                        <button type="button" class="btn btn-success btn-lg" onclick="showDeliveryInfo()"><i class="fa-solid fa-truck-fast me-2"></i>Fast Delivery</button>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hero-panel floating-panel">
                        <img src="./photo/happy-two-women-with-colorful-shopping-bags-blue-wall_231208-11829.jpg" alt="Happy online shopping" onerror="this.src='./photo/16052288-christmas-shopping-girl-with-bags-in-shopping-mall.webp'">
                        <div class="p-4"><div class="d-flex justify-content-between gap-3 flex-wrap"><div><strong>20% off</strong><br><span class="text-muted">Use ZICTURE20</span></div><div><strong>ProductBook</strong><br><a href="product-book.php" class="text-decoration-none">Open catalog</a></div><div><strong>Currency</strong><br><span class="text-muted"><?php echo zicture_currency(); ?></span></div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container my-5" id="services"><div class="row g-3"><div class="col-md-6 col-xl-3"><a href="products.php?category=all&sort=popular" class="text-decoration-none"><div class="service-card h-100 p-4"><span class="icon-tile mb-3"><i class="fa-solid fa-fire"></i></span><h5>Hot Deals</h5><p class="text-muted mb-0">Open popular products sorted by rating.</p></div></a></div><div class="col-md-6 col-xl-3"><a href="cart.php" class="text-decoration-none"><div class="service-card h-100 p-4"><span class="icon-tile mb-3"><i class="fa-solid fa-cart-shopping"></i></span><h5>Easy Cart</h5><p class="text-muted mb-0">Review cart, coupon, checkout, and invoice.</p></div></a></div><div class="col-md-6 col-xl-3"><button type="button" class="service-button text-start" onclick="showDeliveryInfo()"><div class="service-card h-100 p-4"><span class="icon-tile mb-3"><i class="fa-solid fa-truck-fast"></i></span><h5>Fast Delivery</h5><p class="text-muted mb-0">Check delivery rules and set location.</p></div></button></div><div class="col-md-6 col-xl-3"><a href="contact.php#support-center" class="text-decoration-none"><div class="service-card h-100 p-4"><span class="icon-tile mb-3"><i class="fa-solid fa-headset"></i></span><h5>Support Center</h5><p class="text-muted mb-0">Get help, phone support, and answers.</p></div></a></div></div></section>

    <section class="container my-5"><div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-4"><div><h2 class="section-title">Featured Categories</h2><p class="section-subtitle">More departments for every product type.</p></div><a class="btn btn-outline-primary" href="product-book.php">Open ProductBook</a></div><div class="row g-3"><?php foreach ($categories as $key => $cat): ?><div class="col-6 col-md-4 col-xl-3"><a href="products.php?category=<?php echo urlencode($key); ?>" class="text-decoration-none"><div class="category-card h-100 p-4 text-center"><i class="fa-solid fa-<?php echo htmlspecialchars($cat['icon']); ?> fa-2x <?php echo htmlspecialchars($cat['color']); ?> mb-3"></i><h5 class="mb-1"><?php echo htmlspecialchars($cat['label']); ?></h5><p class="text-muted small mb-0">Browse Products</p></div></a></div><?php endforeach; ?></div></section>

    <section class="container my-5" id="coupon-section"><div class="coupon-panel"><div><p class="text-warning fw-bold mb-2">Usable coupon</p><h2 class="fw-bold mb-2">Save 20% with ZICTURE20</h2><p class="mb-0 text-white-50">Copy the code here, then apply it in cart. The cart calculates discount, tax, shipping, and total in your selected currency.</p></div><div class="coupon-box"><code>ZICTURE20</code><button type="button" class="btn btn-warning" onclick="copyCoupon('ZICTURE20')">Copy Coupon</button><a href="cart.php" class="btn btn-outline-light">Go to Cart</a></div></div></section>

    <section class="container my-5" id="daily-section"><div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-4"><div><h2 class="section-title">Daily Section</h2><p class="section-subtitle">Fresh picks for today with fast cart actions.</p></div><a class="btn btn-outline-primary" href="products.php?category=all&sort=popular">See Popular</a></div><div class="row g-4"><?php foreach (array_slice($hotProducts, 0, 4) as $product): ?><div class="col-md-6 col-xl-3"><div class="daily-card h-100"><span class="daily-badge">Daily Pick</span><img src="./photo/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='./photo/Z_Energy_logo.png'"><div class="p-3"><h6 class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></h6><p class="text-muted small mb-2"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 64)); ?>...</p><div class="d-flex justify-content-between align-items-center"><strong class="text-success"><?php echo zicture_money((float)$product['price']); ?></strong><button class="btn btn-sm btn-buy" onclick="zictureAddToCart(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">Cart</button></div></div></div></div><?php endforeach; ?></div></section>

    <section class="container my-5" id="trending"><div class="animated-strip"><div><h2 class="fw-bold mb-2">Trending On Zicture</h2><p class="mb-0 text-white-50">Hot deals, easy cart, fast delivery, support center, and ProductBook are ready.</p></div><div class="d-flex gap-2 flex-wrap"><a href="products.php?category=all&sort=popular" class="btn btn-warning">Hot Deals</a><a href="cart.php" class="btn btn-outline-light">Easy Cart</a><button type="button" class="btn btn-outline-light" onclick="showDeliveryInfo()">Fast Delivery</button></div></div></section>

    <section class="container my-5"><div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-4"><div><h2 class="section-title">Featured Products</h2><p class="section-subtitle">Add to cart, buy now, wishlist, and compare all work from here.</p></div><a class="btn btn-outline-primary" href="products.php?category=all">View All Products</a></div><div class="row g-4"><?php foreach ($products as $product): ?><div class="col-md-6 col-xl-3"><div class="product-card h-100 d-flex flex-column"><div class="product-image-wrap"><img src="./photo/<?php echo htmlspecialchars($product['image']); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='./photo/Z_Energy_logo.png'"></div><div class="card-body d-flex flex-column p-3"><h6 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h6><div class="product-rating mb-2"><?php for ($i = 0; $i < (int)$product['rating']; $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?><?php for ($i = (int)$product['rating']; $i < 5; $i++): ?><i class="fa-regular fa-star"></i><?php endfor; ?></div><p class="text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 78)); ?>...</p><h5 class="product-price mb-3"><?php echo zicture_money((float)$product['price']); ?></h5><div class="product-actions"><button class="btn btn-buy btn-sm wide" onclick="zictureAddToCart(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')"><i class="fa-solid fa-cart-plus me-1"></i>Add to Cart</button><button class="btn btn-success btn-sm" onclick="zictureBuyNow(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">Buy Now</button><button class="btn btn-outline-danger btn-sm" onclick="zictureWishlist(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')"><i class="fa-solid fa-heart"></i></button><button class="btn btn-outline-primary btn-sm wide" onclick="zictureCompare(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')"><i class="fa-solid fa-right-left me-1"></i>Compare</button></div></div></div></div><?php endforeach; ?></div></section>

    <section class="container my-5" id="upcoming"><div class="upcoming-panel"><div class="upcoming-intro"><div class="upcoming-orbit"><i class="fa-solid fa-box-open"></i></div><p class="text-primary fw-bold mb-2 mt-3">Coming soon</p><h2 class="section-title">Upcoming Product Launches</h2><p class="text-muted mb-4">Preview new books, devices, lifestyle bundles, daily essentials, and festival deals.</p><a class="btn btn-primary" href="products.php?category=all&sort=new">Browse New Items</a></div><div class="upcoming-grid"><?php foreach ($upcomingProducts as $product): ?><article class="upcoming-card"><img src="./photo/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='./photo/Z_Energy_logo.png'"><div><span><?php echo htmlspecialchars(zicture_category_label($product['category'])); ?></span><strong><?php echo htmlspecialchars($product['name']); ?></strong><small><?php echo zicture_money((float)$product['price']); ?></small><div class="d-flex gap-2 mt-2"><a class="btn btn-sm btn-outline-primary" href="products.php?q=<?php echo urlencode($product['name']); ?>">Details</a><button class="btn btn-sm btn-buy" onclick="zictureWishlist(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">Save</button></div></div></article><?php endforeach; ?></div></div></section>

    <section class="container my-5"><div class="row justify-content-center"><div class="col-lg-8"><div class="feedback-card bg-white p-4"><h2 class="text-center mb-4">Send us Your Feedback</h2><form method="POST" action="submit-feedback.php"><div class="row g-3"><div class="col-md-6"><label class="form-label">First Name</label><input type="text" class="form-control" name="fname" required></div><div class="col-md-6"><label class="form-label">Last Name</label><input type="text" class="form-control" name="lname"></div><div class="col-12"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div><div class="col-12"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="4" required></textarea></div><div class="col-12 d-flex gap-2 flex-wrap"><button type="reset" class="btn btn-outline-secondary">Reset</button><button type="submit" class="btn btn-primary">Submit Feedback</button></div></div></form></div></div></div></section>
</main>
<?php include 'footer-include.php'; ?>
