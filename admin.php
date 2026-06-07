<?php
// admin.php - Lightweight product and order management for Zicture
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

$notice = '';
$noticeType = 'success';
$categories = zicture_categories();
unset($categories['all']);

$productColumns = [];
try {
    foreach ($pdo->query('SHOW COLUMNS FROM products')->fetchAll() as $column) {
        $productColumns[$column['Field']] = true;
    }
} catch (Exception $e) {
    $productColumns = [];
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'admin_login') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $isValid = false;
            try {
                $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ?');
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                $isValid = $admin && password_verify($password, $admin['password_hash']);
            } catch (Exception $e) {
                $isValid = $username === 'admin' && $password === 'admin123';
            }
            if (!$isValid) {
                throw new Exception('Invalid admin login. Try admin / admin123 after running setup-database.php.');
            }
            $_SESSION['admin_logged_in'] = true;
            header('Location: admin.php');
            exit;
        }

        if (empty($_SESSION['admin_logged_in'])) {
            throw new Exception('Please login as admin first.');
        }

        if ($action === 'save_product') {
            $id = (int)($_POST['product_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $category = strtolower(trim($_POST['category'] ?? 'food'));
            $price = max(0, (float)($_POST['price'] ?? 0));
            $stock = max(0, (int)($_POST['stock'] ?? 0));
            $rating = min(5, max(0, (int)($_POST['rating'] ?? 0)));
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? 'Z_Energy_logo.png');
            if ($name === '') {
                throw new Exception('Product name is required.');
            }
            if (!isset($categories[$category])) {
                $category = 'food';
            }

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE products SET name = ?, category = ?, price = ?, description = ?, image = ?, rating = ?, stock = ? WHERE id = ?');
                $stmt->execute([$name, $category, $price, $description, $image, $rating, $stock, $id]);
                if (isset($productColumns['is_daily'])) {
                    $flagStmt = $pdo->prepare('UPDATE products SET is_daily = ?, is_upcoming = ?, is_featured = ? WHERE id = ?');
                    $flagStmt->execute([
                        isset($_POST['is_daily']) ? 1 : 0,
                        isset($_POST['is_upcoming']) ? 1 : 0,
                        isset($_POST['is_featured']) ? 1 : 0,
                        $id
                    ]);
                }
                $notice = 'Product updated.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO products (name, category, price, description, image, rating, stock) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $category, $price, $description, $image, $rating, $stock]);
                $id = (int)$pdo->lastInsertId();
                if (isset($productColumns['is_daily'])) {
                    $flagStmt = $pdo->prepare('UPDATE products SET is_daily = ?, is_upcoming = ?, is_featured = ? WHERE id = ?');
                    $flagStmt->execute([
                        isset($_POST['is_daily']) ? 1 : 0,
                        isset($_POST['is_upcoming']) ? 1 : 0,
                        isset($_POST['is_featured']) ? 1 : 0,
                        $id
                    ]);
                }
                $notice = 'Product added.';
            }
        } elseif ($action === 'update_order_status') {
            $orderId = (int)($_POST['order_id'] ?? 0);
            $status = strtolower(trim($_POST['status'] ?? ''));
            $allowedStatuses = ['confirmed', 'processing', 'shipped', 'completed', 'cancelled'];
            if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {
                throw new Exception('Invalid order status update.');
            }
            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$status, $orderId]);
            $notice = 'Order #' . $orderId . ' changed to ' . ucfirst($status) . '.';
        } elseif ($action === 'delete_product') {
            $id = (int)($_POST['product_id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM order_items WHERE product_id = ?');
                $stmt->execute([$id]);
                if ((int)$stmt->fetch()['count'] > 0) {
                    throw new Exception('This product is in order history. Set stock to 0 instead of deleting it.');
                }
                $pdo->beginTransaction();
                $pdo->prepare('DELETE FROM wishlist WHERE product_id = ?')->execute([$id]);
                $pdo->prepare('DELETE FROM cart WHERE product_id = ?')->execute([$id]);
                $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
                $pdo->commit();
                $notice = 'Product deleted.';
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $notice = $e->getMessage();
        $noticeType = 'danger';
    }
}

$editProduct = null;
if (!empty($_SESSION['admin_logged_in']) && isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

$products = [];
$orders = [];
$stats = ['products' => 0, 'orders' => 0, 'revenue' => 0, 'low_stock' => 0, 'pending' => 0];
$statusClasses = [
    'confirmed' => 'bg-primary',
    'processing' => 'bg-info text-dark',
    'shipped' => 'bg-warning text-dark',
    'completed' => 'bg-success',
    'cancelled' => 'bg-danger',
    'pending' => 'bg-secondary',
];
if (!empty($_SESSION['admin_logged_in'])) {
    try {
        $products = $pdo->query('SELECT * FROM products ORDER BY created_at DESC, id DESC LIMIT 80')->fetchAll();
        $orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC, id DESC LIMIT 20')->fetchAll();
        $stats['products'] = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $stats['orders'] = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $stats['revenue'] = (float)$pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status <> 'cancelled'")->fetchColumn();
        $stats['low_stock'] = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock <= 10')->fetchColumn();
        $stats['pending'] = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'confirmed', 'processing')")->fetchColumn();
    } catch (Exception $e) {
        $notice = 'Admin data could not load: ' . $e->getMessage();
        $noticeType = 'danger';
    }
}

$title = 'Admin - Zicture';
include 'header-include.php';
?>

<main class="container my-5 admin-page">
    <div class="admin-hero mb-4">
        <div>
            <p class="text-warning fw-bold mb-2">Store control</p>
            <h1 class="display-6 fw-bold mb-1"><i class="fa-solid fa-gauge-high me-2 text-primary"></i>Zicture Admin</h1>
            <p class="mb-0">Manage products, categories, homepage sections, stock, pricing, and customer orders.</p>
        </div>
        <?php if (!empty($_SESSION['admin_logged_in'])): ?>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-warning" href="products.php?category=all">View Store</a>
                <a class="btn btn-outline-light" href="admin.php?logout=1">Logout Admin</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($notice): ?><div class="alert alert-<?php echo $noticeType; ?>"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>

    <?php if (empty($_SESSION['admin_logged_in'])): ?>
        <section class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="feedback-card bg-white p-4">
                    <h2 class="h4 fw-bold mb-3">Admin Login</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="admin_login">
                        <div class="mb-3"><label class="form-label">Username</label><input class="form-control form-control-lg" name="username" value="admin" required></div>
                        <div class="mb-3"><label class="form-label">Password</label><input class="form-control form-control-lg" type="password" name="password" placeholder="admin123" required></div>
                        <button class="btn btn-primary btn-lg w-100" type="submit">Open Admin</button>
                    </form>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="admin-stat-grid mb-4">
            <div class="admin-stat"><span><i class="fa-solid fa-box"></i></span><strong><?php echo number_format($stats['products']); ?></strong><small>Products</small></div>
            <div class="admin-stat"><span><i class="fa-solid fa-receipt"></i></span><strong><?php echo number_format($stats['orders']); ?></strong><small>Total Orders</small></div>
            <div class="admin-stat"><span><i class="fa-solid fa-money-bill-wave"></i></span><strong><?php echo zicture_money($stats['revenue']); ?></strong><small>Revenue</small></div>
            <div class="admin-stat"><span><i class="fa-solid fa-triangle-exclamation"></i></span><strong><?php echo number_format($stats['low_stock']); ?></strong><small>Low Stock</small></div>
            <div class="admin-stat"><span><i class="fa-solid fa-clock"></i></span><strong><?php echo number_format($stats['pending']); ?></strong><small>Active Orders</small></div>
        </section>

        <div class="row g-4">
            <section class="col-xl-4">
                <div class="summary-card bg-white p-4 sticky-xl-top" style="top: 92px;">
                    <h2 class="h4 fw-bold mb-3"><?php echo $editProduct ? 'Edit Product' : 'Add Product'; ?></h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_product">
                        <input type="hidden" name="product_id" value="<?php echo (int)($editProduct['id'] ?? 0); ?>">
                        <div class="mb-3"><label class="form-label">Product Name</label><input class="form-control" name="name" value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Category</label><select class="form-select" name="category"><?php foreach ($categories as $key => $meta): ?><option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($editProduct['category'] ?? '') === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($meta['label']); ?></option><?php endforeach; ?></select></div>
                        <div class="row g-2">
                            <div class="col-6 mb-3"><label class="form-label">Price</label><input class="form-control" type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($editProduct['price'] ?? ''); ?>" required></div>
                            <div class="col-6 mb-3"><label class="form-label">Stock</label><input class="form-control" type="number" name="stock" value="<?php echo htmlspecialchars($editProduct['stock'] ?? '100'); ?>" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Rating</label><input class="form-control" type="number" min="0" max="5" name="rating" value="<?php echo htmlspecialchars($editProduct['rating'] ?? '4'); ?>"></div>
                        <div class="mb-3"><label class="form-label">Image File in photo/</label><input class="form-control" name="image" value="<?php echo htmlspecialchars($editProduct['image'] ?? 'Z_Energy_logo.png'); ?>"></div>
                        <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea></div>
                        <?php if (isset($productColumns['is_daily'])): ?>
                            <div class="admin-check-grid mb-3">
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="is_daily" <?php echo !empty($editProduct['is_daily']) ? 'checked' : ''; ?>><span class="form-check-label">Daily Pick</span></label>
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="is_upcoming" <?php echo !empty($editProduct['is_upcoming']) ? 'checked' : ''; ?>><span class="form-check-label">Upcoming</span></label>
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="is_featured" <?php echo !isset($editProduct['is_featured']) || !empty($editProduct['is_featured']) ? 'checked' : ''; ?>><span class="form-check-label">Featured</span></label>
                            </div>
                        <?php endif; ?>
                        <button class="btn btn-success w-100" type="submit"><?php echo $editProduct ? 'Update Product' : 'Add Product'; ?></button>
                        <?php if ($editProduct): ?><a class="btn btn-outline-secondary w-100 mt-2" href="admin.php">Cancel Edit</a><?php endif; ?>
                    </form>
                </div>
            </section>

            <section class="col-xl-8">
                <div class="feedback-card bg-white p-3 p-md-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                        <h2 class="h4 fw-bold mb-0">Products</h2>
                        <a class="btn btn-sm btn-outline-primary" href="product-book.php">Open ProductBook</a>
                    </div>
                    <div class="table-responsive table-responsive-stack">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><div class="d-flex align-items-center gap-2"><img class="admin-thumb" src="./photo/<?php echo htmlspecialchars($product['image']); ?>" alt="" onerror="this.src='./photo/Z_Energy_logo.png'"><strong><?php echo htmlspecialchars($product['name']); ?></strong></div></td>
                                        <td><?php echo htmlspecialchars(zicture_category_label($product['category'])); ?></td>
                                        <td><?php echo zicture_money((float)$product['price']); ?></td>
                                        <td><span class="badge <?php echo (int)$product['stock'] <= 10 ? 'bg-danger' : 'bg-light text-dark'; ?>"><?php echo (int)$product['stock']; ?></span></td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <a class="btn btn-sm btn-outline-primary" href="admin.php?edit=<?php echo (int)$product['id']; ?>">Edit</a>
                                                <form method="POST" onsubmit="return confirm('Delete this product?');">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="feedback-card bg-white p-3 p-md-4">
                    <h2 class="h4 fw-bold mb-3">Recent Orders</h2>
                    <?php if (!$orders): ?>
                        <p class="text-muted mb-0">No orders yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Manage</th></tr></thead>
                                <tbody><?php foreach ($orders as $order): ?><tr>
                                    <td><strong>#<?php echo (int)$order['id']; ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($order['created_at'] ?? ''); ?></small></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? $order['session_id']); ?><br><small class="text-muted"><?php echo htmlspecialchars($order['delivery_city'] ?? 'No city'); ?></small></td>
                                    <td><?php echo zicture_money((float)$order['total_price']); ?></td>
                                    <td><span class="badge <?php echo $statusClasses[$order['status']] ?? 'bg-secondary'; ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                                    <td>
                                        <div class="admin-order-actions">
                                            <a class="btn btn-sm btn-outline-success" href="invoice.php?order_id=<?php echo (int)$order['id']; ?>">PDF</a>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="update_order_status">
                                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                                <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                                                    <?php foreach (['confirmed', 'processing', 'shipped', 'completed', 'cancelled'] as $status): ?>
                                                        <option value="<?php echo $status; ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </div>
                                    </td>
                                </tr><?php endforeach; ?></tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer-include.php'; ?>
