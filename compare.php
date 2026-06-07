<?php
// compare.php - Compare real selected products
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'helpers.php';

$ids = $_SESSION['compare_products'] ?? [];
$ids = array_values(array_unique(array_map('intval', $ids)));
$compareProducts = [];

if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    foreach ($ids as $id) {
        foreach ($rows as $row) {
            if ((int)$row['id'] === $id) {
                $compareProducts[] = $row;
                break;
            }
        }
    }
}

$title = 'Compare Products - Zicture';
include 'header-include.php';
?>

<main class="container my-5">
    <div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-4">
        <div>
            <h1 class="display-6 fw-bold mb-2"><i class="fa-solid fa-right-left text-primary me-2"></i>Compare Products</h1>
            <p class="text-muted mb-0">Compare up to 4 selected products side by side.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="products.php?category=all" class="btn btn-primary">Add Products</a>
            <?php if ($compareProducts): ?><button class="btn btn-outline-danger" onclick="clearCompare()">Clear Comparison</button><?php endif; ?>
        </div>
    </div>

    <?php if (!$compareProducts): ?>
        <div class="alert alert-info text-center py-5">
            <i class="fa-solid fa-scale-balanced fa-3x mb-3"></i>
            <h4>No products selected</h4>
            <p>Use the Compare button on product cards to add items here.</p>
            <a href="products.php?category=all" class="btn btn-primary">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle bg-white shadow-sm">
                <tbody>
                    <tr>
                        <th style="width: 160px;">Product</th>
                        <?php foreach ($compareProducts as $product): ?>
                            <td class="text-center">
                                <img src="./photo/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="rounded mb-2" style="width: 120px; height: 100px; object-fit: cover;" onerror="this.src='./photo/Z_Energy_logo.png'">
                                <h6 class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeCompare(<?php echo (int)$product['id']; ?>)">Remove</button>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr><th>Price</th><?php foreach ($compareProducts as $product): ?><td class="text-center text-success fw-bold"><?php echo zicture_money((float)$product['price']); ?></td><?php endforeach; ?></tr>
                    <tr><th>Rating</th><?php foreach ($compareProducts as $product): ?><td class="text-center product-rating"><?php for ($i = 0; $i < (int)$product['rating']; $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?></td><?php endforeach; ?></tr>
                    <tr><th>Category</th><?php foreach ($compareProducts as $product): ?><td class="text-center text-capitalize"><?php echo htmlspecialchars($product['category']); ?></td><?php endforeach; ?></tr>
                    <tr><th>Description</th><?php foreach ($compareProducts as $product): ?><td><?php echo htmlspecialchars($product['description'] ?? ''); ?></td><?php endforeach; ?></tr>
                    <tr><th>Action</th><?php foreach ($compareProducts as $product): ?><td class="text-center"><button class="btn btn-buy btn-sm" onclick="zictureBuyNow(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">Buy Now</button></td><?php endforeach; ?></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<script>
function comparePost(action, productId) {
    const formData = new FormData();
    formData.append('action', action);
    if (productId) formData.append('product_id', productId);
    fetch('compare-action.php', { method: 'POST', body: formData }).then(() => window.location.reload());
}
function removeCompare(productId) { comparePost('remove', productId); }
function clearCompare() { comparePost('clear'); }
</script>

<?php include 'footer-include.php'; ?>
