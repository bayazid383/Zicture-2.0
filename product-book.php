<?php
use Dompdf\Dompdf;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'helpers.php';

try {
    $stmt = $pdo->query('SELECT * FROM products ORDER BY category, name');
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $products = [];
}

$grouped = [];
foreach ($products as $product) {
    $grouped[$product['category']][] = $product;
}


// ================= PDF DOWNLOAD =================
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {

    require_once 'vendor/autoload.php';
    $dompdf = new Dompdf();

    ob_start();
?>

<html>
<body style="font-family: Arial, sans-serif; padding:20px; font-size:12px;">

<!-- HEADER -->
<div style="text-align:center; border-bottom:2px solid #111; padding-bottom:10px; margin-bottom:20px;">
    <h1 style="margin:0;">Zicture Product Catalog</h1>
    <p style="color:#555;">
        Generated: <?php echo date('Y-m-d H:i'); ?> |
        Total Products: <?php echo count($products); ?>
    </p>
</div>

<?php foreach ($grouped as $category => $items): ?>

<h2 style="background:#111827;color:#fff;padding:8px;font-size:14px;">
    <?php echo htmlspecialchars($category); ?>
</h2>

<table style="width:100%; border-collapse:collapse; margin-bottom:20px;">

<!-- HEADER ROW -->
<tr style="background:#111827;color:#fff;">
    <th style="border:1px solid #ccc;padding:6px;">Name</th>
    <th style="border:1px solid #ccc;padding:6px;">Price</th>
    <th style="border:1px solid #ccc;padding:6px;">Stock</th>
    <th style="border:1px solid #ccc;padding:6px;">Rating</th>
</tr>

<?php $i = 0; ?>
<?php foreach ($items as $product): ?>

<tr style="background: <?php echo ($i++ % 2 == 0) ? '#ffffff' : '#f9fafb'; ?>;">

<td style="border:1px solid #ccc;padding:6px;">
<?php echo htmlspecialchars($product['name']); ?>
</td>

<td style="border:1px solid #ccc;padding:6px;color:#198754;">
<?php echo zicture_money((float)$product['price']); ?>
</td>

<td style="border:1px solid #ccc;padding:6px;">
<?php echo (int)$product['stock']; ?>
</td>

<td style="border:1px solid #ccc;padding:6px;color:#f59e0b;text-align:center;">

<?php
$rating = (int)$product['rating'];
$max = 5;
$stars = '';

for ($j = 1; $j <= $max; $j++) {
    $stars .= ($j <= $rating) ? '★' : '☆';
}
echo $stars;
?>

</td>

</tr>

<?php endforeach; ?>

</table>

<?php endforeach; ?>

</body>
</html>

<?php
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');

    ob_clean();
    $dompdf->render();
    $dompdf->stream("zicture-product-book.pdf", ["Attachment" => true]);

    exit;
}
// ================= END PDF =================


// NORMAL PAGE
$title = 'ProductBook - Zicture';
include 'header-include.php';
?>

<main class="container my-5">

<!-- HERO -->
<section class="mb-5" style="
background: linear-gradient(135deg, #0f172a, #1e3a8a, #0f766e);
padding:50px;
border-radius:12px;
color:white;
">

<p class="text-warning fw-bold mb-2">Digital catalog</p>

<h1 class="display-5 fw-bold mb-3">Zicture ProductBook</h1>

<p class="lead text-white-50 mb-4">
A clean catalog view of every department, with prices shown in your selected currency.
</p>

<div class="d-flex gap-2 flex-wrap">
<a class="btn btn-warning btn-lg" href="products.php?category=all">Shop All Items</a>

<a class="btn btn-outline-light btn-lg" href="product-book.php?download=pdf">
<i class="fa-solid fa-file-pdf me-2"></i>Download PDF
</a>

<button class="btn btn-outline-light btn-lg" onclick="window.print()">
<i class="fa-solid fa-print me-2"></i>Print Catalog
</button>
</div>

</section>

<?php if (!$products): ?>
<div class="alert alert-info text-center py-5">No products found.</div>

<?php else: ?>

<?php foreach ($grouped as $category => $items): ?>

<section class="mb-5">

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">

<h2 class="fw-bold text-capitalize" style="border-left:5px solid #0d6efd;padding-left:10px;">
<?php echo htmlspecialchars($category); ?>
</h2>

<a class="btn btn-sm btn-outline-primary"
href="products.php?category=<?php echo urlencode($category); ?>">
Shop
</a>

</div>

<div class="row g-4">

<?php foreach ($items as $product): ?>

<div class="col-md-6 col-xl-4">

<article class="h-100" style="
border-radius:10px;
overflow:hidden;
background:#fff;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
transition:0.3s;
"
onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 10px 25px rgba(0,0,0,0.15)';"
onmouseout="this.style.transform='none';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)';"
>

<div style="position:relative;">
<img src="./photo/<?php echo htmlspecialchars($product['image']); ?>"
style="height:180px;object-fit:cover;width:100%;"
onerror="this.src='./photo/Z_Energy_logo.png'">

<div style="
position:absolute;
top:10px;
left:10px;
background:<?php echo ($product['stock'] > 0 ? '#16a34a' : '#dc2626'); ?>;
color:#fff;
padding:3px 8px;
font-size:11px;
border-radius:5px;
">
<?php echo ($product['stock'] > 0 ? 'In Stock' : 'Out of Stock'); ?>
</div>
</div>

<div class="p-3">

<h5 class="fw-bold mb-1">
<?php echo htmlspecialchars($product['name']); ?>
</h5>

<!-- ⭐ WEBSITE RATING -->
<div style="color:#f59e0b; font-size:14px; margin-bottom:6px;">
<?php echo str_repeat('★', (int)$product['rating']); ?>
</div>

<p class="text-muted small mb-3">
<?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)); ?>...
</p>

<div class="d-flex justify-content-between align-items-center">

<strong style="color:#198754;">
<?php echo zicture_money((float)$product['price']); ?>
</strong>

<button class="btn btn-sm btn-primary"
onclick="zictureBuyNow(<?php echo (int)$product['id']; ?>,'<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">
Buy
</button>

</div>

</div>

</article>

</div>

<?php endforeach; ?>

</div>

</section>

<?php endforeach; ?>

<?php endif; ?>

</main>

<?php include 'footer-include.php'; ?>