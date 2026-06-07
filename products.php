<?php
// products.php - Display products filtered by category/search
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

$category = isset($_GET['category']) ? strtolower(trim($_GET['category'])) : 'all';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : '';
$categories = zicture_categories();
$allowedCategories = array_keys($categories);
if (!in_array($category, $allowedCategories, true)) {
    $category = 'all';
}

try {
    $where = [];
    $params = [];
    if ($category !== 'all') {
        $where[] = 'category = ?';
        $params[] = $category;
    }
    if ($q !== '') {
        $where[] = '(name LIKE ? OR category LIKE ? OR description LIKE ?)';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }
    $sql = 'SELECT * FROM products';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    if ($sort === 'new') {
        $sql .= ' ORDER BY created_at DESC, id DESC';
    } elseif ($sort === 'price_low') {
        $sql .= ' ORDER BY price ASC, name ASC';
    } elseif ($sort === 'price_high') {
        $sql .= ' ORDER BY price DESC, name ASC';
    } else {
        $sql .= ' ORDER BY rating DESC, name ASC';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $display_products = $stmt->fetchAll();
} catch (Exception $e) {
    $display_products = [];
}

$page_title = $category === 'all' ? 'All Products' : zicture_category_label($category) . ' Products';
if ($q !== '') {
    $page_title = 'Search: ' . $q;
}
$title = $page_title . ' - Zicture';
include 'header-include.php';

?>

<main class="container my-5">
    <div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-4">
        <div>
            <h1 class="display-6 fw-bold mb-2"><?php echo htmlspecialchars($page_title); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($page_title); ?></li>
                </ol>
            </nav>
        </div>
        <form class="catalog-search" method="GET" action="products.php">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <input class="form-control" type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search this category">
            <select class="form-select" name="sort" aria-label="Sort products">
                <option value="" <?php echo $sort === '' ? 'selected' : ''; ?>>Popular</option>
                <option value="new" <?php echo $sort === 'new' ? 'selected' : ''; ?>>Newest</option>
                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Low Price</option>
                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>High Price</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
    </div>

    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="summary-card bg-white p-3 sticky-lg-top" style="top: 92px;">
                <h5 class="fw-bold mb-3">Filter by Category</h5>
                <div class="list-group list-group-flush">
                    <?php foreach ($categories as $key => $meta): ?>
                        <a href="products.php?category=<?php echo urlencode($key); ?><?php echo $q !== '' ? '&q=' . urlencode($q) : ''; ?>" class="list-group-item list-group-item-action <?php echo $category === $key ? 'active' : ''; ?>">
                            <i class="fa-solid fa-<?php echo htmlspecialchars($meta['icon']); ?> me-2"></i><?php echo htmlspecialchars($meta['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="deal-band mt-4 p-3">
                    <h6 class="fw-bold">Coupon</h6>
                    <p class="small text-white-50 mb-2">Use ZICTURE20 for 20% off in cart.</p>
                    <button class="btn btn-warning btn-sm w-100" type="button" onclick="copyCoupon('ZICTURE20')">Copy Coupon</button>
                </div>
            </div>
        </aside>

        <section class="col-lg-9">
            <?php if (!empty($display_products)): ?>
                <div class="row g-4">
                    <?php foreach ($display_products as $product): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="product-card h-100 d-flex flex-column">
                                <div class="product-image-wrap">
                                    <img src="./photo/<?php echo htmlspecialchars($product['image']); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='./photo/Z_Energy_logo.png'">
                                </div>
                                <div class="card-body d-flex flex-column p-3">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <h6 class="product-name mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <button class="btn btn-outline-danger btn-sm" onclick="zictureWishlist(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')" title="Add to Wishlist"><i class="fa-solid fa-heart"></i></button>
                                    </div>
                                    <div class="product-rating mb-2">
                                        <?php for ($i = 0; $i < (int)$product['rating']; $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                                        <?php for ($i = (int)$product['rating']; $i < 5; $i++): ?><i class="fa-regular fa-star"></i><?php endfor; ?>
                                        <small class="text-muted">(<?php echo (int)$product['rating']; ?>/5)</small>
                                    </div>
                                    <p class="text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 95)); ?>...</p>
                                    <h5 class="product-price mb-3"><?php echo zicture_money((float)$product['price']); ?></h5>
                                    <div class="product-actions">
                                        <button class="btn btn-buy btn-sm wide" onclick="zictureAddToCart(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')"><i class="fa-solid fa-cart-plus me-1"></i>Add to Cart</button>
                                        <button class="btn btn-success btn-sm" onclick="zictureBuyNow(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">Buy Now</button>
                                        <button class="btn btn-outline-primary btn-sm" onclick="zictureCompare(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')"><i class="fa-solid fa-right-left me-1"></i>Compare</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center py-5">
                    <i class="fa-solid fa-inbox fa-3x mb-3"></i>
                    <h4>No products found</h4>
                    <p>Try another category or search term.</p>
                    <a href="products.php?category=all" class="btn btn-primary">Browse All Products</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'footer-include.php'; ?>
