<?php
// cart.php - Shopping cart display, coupon, and checkout
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
$invoiceOrderId = isset($_GET['ordered']) ? (int)$_GET['ordered'] : 0;
if ($invoiceOrderId > 0) {
    $notice = 'Order #' . $invoiceOrderId . ' placed successfully. Your invoice is ready to download.';
}

function getCartItems(PDO $pdo): array {
    $stmt = $pdo->prepare('SELECT c.id as cart_id, c.quantity, p.id, p.name, p.price, p.image, p.category FROM cart c JOIN products p ON c.product_id = p.id WHERE c.session_id = ? ORDER BY c.added_at DESC');
    $stmt->execute([$_SESSION['session_id']]);
    return $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

    try {
        if ($action === 'remove' && $cart_id > 0) {
            $stmt = $pdo->prepare('DELETE FROM cart WHERE id = ? AND session_id = ?');
            $stmt->execute([$cart_id, $_SESSION['session_id']]);
            $notice = 'Product removed from cart.';
        } elseif ($action === 'update' && $cart_id > 0) {
            if ($quantity > 0) {
                $stmt = $pdo->prepare('UPDATE cart SET quantity = ? WHERE id = ? AND session_id = ?');
                $stmt->execute([min($quantity, 100), $cart_id, $_SESSION['session_id']]);
                $notice = 'Cart quantity updated.';
            }
        } elseif ($action === 'clear') {
            $stmt = $pdo->prepare('DELETE FROM cart WHERE session_id = ?');
            $stmt->execute([$_SESSION['session_id']]);
            unset($_SESSION['coupon_code']);
            $notice = 'Cart cleared.';
        } elseif ($action === 'coupon') {
            $code = strtoupper(trim($_POST['promo_code'] ?? ''));
            if ($code === 'ZICTURE20') {
                $_SESSION['coupon_code'] = $code;
                $notice = 'Coupon ZICTURE20 applied. You saved 20%.';
            } else {
                unset($_SESSION['coupon_code']);
                $notice = 'Invalid coupon code. Try ZICTURE20.';
                $noticeType = 'danger';
            }
        } elseif ($action === 'checkout') {
            $cartItemsForOrder = getCartItems($pdo);
            if (!$cartItemsForOrder) {
                throw new Exception('Your cart is empty.');
            }

            $subtotalForOrder = 0;
            foreach ($cartItemsForOrder as $item) {
                $subtotalForOrder += (float)$item['price'] * (int)$item['quantity'];
            }
            $discountForOrder = ($_SESSION['coupon_code'] ?? '') === 'ZICTURE20' ? $subtotalForOrder * 0.20 : 0;
            $taxForOrder = ($subtotalForOrder - $discountForOrder) * 0.05;
            $shippingForOrder = $subtotalForOrder >= 500 ? 0 : 80;
            $totalForOrder = max(0, $subtotalForOrder - $discountForOrder) + $taxForOrder + $shippingForOrder;

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO orders (session_id, total_price, status) VALUES (?, ?, ?)');
            $stmt->execute([$_SESSION['session_id'], $totalForOrder, 'confirmed']);
            $orderId = (int)$pdo->lastInsertId();

            $existingColumns = [];
            foreach ($pdo->query('SHOW COLUMNS FROM orders')->fetchAll() as $column) {
                $existingColumns[$column['Field']] = true;
            }
            if (isset($existingColumns['subtotal'])) {
                $deliveryAddress = trim(($_SESSION['delivery_street'] ?? '') . ', ' . ($_SESSION['delivery_zipcode'] ?? ''));
                $stmt = $pdo->prepare('UPDATE orders SET subtotal = ?, discount = ?, tax = ?, shipping = ?, coupon_code = ?, customer_name = ?, customer_email = ?, delivery_city = ?, delivery_address = ? WHERE id = ?');
                $stmt->execute([
                    $subtotalForOrder,
                    $discountForOrder,
                    $taxForOrder,
                    $shippingForOrder,
                    $_SESSION['coupon_code'] ?? '',
                    $_SESSION['customer_name'] ?? 'Guest Shopper',
                    $_SESSION['customer_email'] ?? '',
                    $_SESSION['delivery_city'] ?? '',
                    $deliveryAddress,
                    $orderId
                ]);
            }

            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
            foreach ($cartItemsForOrder as $item) {
                $itemStmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
            }

            $stmt = $pdo->prepare('DELETE FROM cart WHERE session_id = ?');
            $stmt->execute([$_SESSION['session_id']]);
            $pdo->commit();
            unset($_SESSION['coupon_code']);
            $_SESSION['last_order_id'] = $orderId;
            header('Location: cart.php?ordered=' . $orderId);
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $notice = $e->getMessage();
        $noticeType = 'danger';
    }
}

$cartItems = getCartItems($pdo);
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}
$couponCode = $_SESSION['coupon_code'] ?? '';
$discount = $couponCode === 'ZICTURE20' ? $subtotal * 0.20 : 0;
$taxable = max(0, $subtotal - $discount);
$tax = $taxable * 0.05;
$shipping = $subtotal >= 500 || $subtotal == 0 ? 0 : 80;
$total = $taxable + $tax + $shipping;
$deliveryCity = $_SESSION['delivery_city'] ?? '';

$title = 'Shopping Cart - Zicture';
include 'header-include.php';
?>

<main class="container my-5">
    <?php if ($notice): ?>
        <div class="alert alert-<?php echo $noticeType; ?> text-center">
            <?php echo htmlspecialchars($notice); ?>
            <?php if ($invoiceOrderId > 0): ?>
                <div class="mt-3"><a class="btn btn-success" href="invoice.php?order_id=<?php echo $invoiceOrderId; ?>"><i class="fa-solid fa-file-pdf me-2"></i>Download PDF Invoice</a></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-4">
        <div>
            <h1 class="display-6 fw-bold mb-2"><i class="fa-solid fa-shopping-cart text-primary me-2"></i>Shopping Cart</h1>
            <p class="text-muted mb-0">Review all carted products before checkout.</p>
        </div>
        <a href="products.php?category=all" class="btn btn-primary">Continue Shopping</a>
    </div>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="fa-solid fa-inbox fa-3x mb-3"></i>
            <h4>Your cart is empty</h4>
            <p>Start shopping and add products to your cart.</p>
            <a href="products.php?category=all" class="btn btn-primary">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <section class="col-lg-8">
                <div class="table-responsive table-responsive-stack">
                    <table class="table table-hover align-middle bg-white shadow-sm">
                        <thead class="table-light">
                            <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="./photo/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" width="64" height="64" class="rounded" onerror="this.src='./photo/Z_Energy_logo.png'">
                                            <div><h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6><span class="badge bg-light text-dark text-capitalize"><?php echo htmlspecialchars($item['category']); ?></span></div>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-primary"><?php echo zicture_money((float)$item['price']); ?></td>
                                    <td>
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="cart_id" value="<?php echo (int)$item['cart_id']; ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" max="100" class="form-control form-control-sm" style="width: 82px;">
                                            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-check"></i></button>
                                        </form>
                                    </td>
                                    <td class="fw-bold text-success"><?php echo zicture_money((float)$item['price'] * (int)$item['quantity']); ?></td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="cart_id" value="<?php echo (int)$item['cart_id']; ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-trash me-2"></i>Clear Cart</button>
                </form>
            </section>

            <aside class="col-lg-4">
                <div class="summary-card bg-white p-4 sticky-lg-top" style="top: 92px;">
                    <h5 class="fw-bold mb-3">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong><?php echo zicture_money($subtotal); ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Discount</span><strong class="text-danger">- <?php echo zicture_money($discount); ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Tax (5%)</span><strong><?php echo zicture_money($tax); ?></strong></div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom"><span>Shipping</span><strong class="text-success"><?php echo $shipping == 0 ? 'FREE' : zicture_money($shipping); ?></strong></div>
                    <div class="d-flex justify-content-between align-items-center mb-4"><span class="fs-5 fw-bold">Total</span><strong class="fs-4 text-primary"><?php echo zicture_money($total); ?></strong></div>

                    <form method="POST" class="mb-3">
                        <input type="hidden" name="action" value="coupon">
                        <label class="form-label" for="promo_code">Promo Code</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="promo_code" name="promo_code" value="<?php echo htmlspecialchars($couponCode); ?>" placeholder="ZICTURE20">
                            <button class="btn btn-outline-primary" type="submit">Apply</button>
                        </div>
                    </form>

                    <div class="alert alert-light border small">
                        <strong>Delivery:</strong> <?php echo $deliveryCity ? htmlspecialchars($deliveryCity) : 'Set your location for delivery details.'; ?><br>
                        Free delivery over <?php echo zicture_money(500); ?> in Dhaka and selected areas.
                    </div>

                    <form method="POST" class="d-grid gap-2">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-success btn-lg"><i class="fa-solid fa-credit-card me-2"></i>Checkout</button>
                        <a href="location.php" class="btn btn-outline-secondary">Set Location</a>
                    </form>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer-include.php'; ?>
