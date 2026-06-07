<?php
use Dompdf\Dompdf;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'helpers.php';

// GET ORDER
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : (int)($_SESSION['last_order_id'] ?? 0);

if ($orderId <= 0) {
    http_response_code(404);
    echo 'No invoice available.';
    exit;
}

$isAdmin = !empty($_SESSION['admin_logged_in']);

$stmt = $isAdmin 
    ? $pdo->prepare('SELECT * FROM orders WHERE id = ?') 
    : $pdo->prepare('SELECT * FROM orders WHERE id = ? AND session_id = ?');

$stmt->execute($isAdmin ? [$orderId] : [$orderId, $_SESSION['session_id'] ?? '']);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo 'Invoice not found.';
    exit;
}

// GET ITEMS
$stmt = $pdo->prepare('
    SELECT oi.quantity, oi.price, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
');
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();


// ================= PDF GENERATION =================
require_once 'vendor/autoload.php';

$dompdf = new Dompdf();

ob_start();
?>

<html>
<body style="font-family: Arial, sans-serif; padding:25px; font-size:12px;">

<!-- HEADER -->
<div style="
    text-align:center;
    border-bottom:3px solid #0d6efd;
    padding-bottom:10px;
    margin-bottom:20px;
">
    <h1 style="margin:0; color:#0d6efd;">Zicture Invoice</h1>
    <p style="margin:5px 0; color:#555;">
        Invoice #<?php echo $orderId; ?> |
        <?php echo date('Y-m-d H:i'); ?>
    </p>
</div>


<!-- CUSTOMER INFO -->
<table style="width:100%; margin-bottom:15px;">
<tr>
<td>
<strong>Customer:</strong> <?php echo $order['customer_name'] ?? $_SESSION['customer_name'] ?? 'Guest'; ?><br>
<strong>Email:</strong> <?php echo $order['customer_email'] ?? $_SESSION['customer_email'] ?? 'N/A'; ?><br>
<strong>City:</strong> <?php echo $order['delivery_city'] ?? 'N/A'; ?><br>
<?php if (!empty($order['delivery_address'])): ?>
<strong>Address:</strong> <?php echo $order['delivery_address']; ?>
<?php endif; ?>
</td>

<td style="text-align:right;">
<strong>Status:</strong> 
<span style="color:#198754;">
<?php echo strtoupper($order['status']); ?>
</span><br>

<strong>Currency:</strong> <?php echo zicture_currency(); ?>
</td>
</tr>
</table>


<!-- ITEMS TABLE -->
<h3 style="
background:#0f172a;
color:#fff;
padding:6px;
font-size:14px;
">Purchased Items</h3>

<table style="width:100%; border-collapse:collapse; margin-top:5px;">

<tr style="background:#f1f1f1;">
<th style="border:1px solid #ccc; padding:6px;">Product</th>
<th style="border:1px solid #ccc; padding:6px;">Qty</th>
<th style="border:1px solid #ccc; padding:6px;">Price</th>
<th style="border:1px solid #ccc; padding:6px;">Total</th>
</tr>

<?php foreach ($items as $item): ?>
<tr>
<td style="border:1px solid #ccc; padding:6px;">
<?php echo htmlspecialchars($item['name']); ?>
</td>

<td style="border:1px solid #ccc; padding:6px; text-align:center;">
<?php echo (int)$item['quantity']; ?>
</td>

<td style="border:1px solid #ccc; padding:6px; color:#0d6efd;">
<?php echo zicture_plain_money((float)$item['price']); ?>
</td>

<td style="border:1px solid #ccc; padding:6px; color:#198754;">
<?php echo zicture_plain_money((float)$item['price'] * (int)$item['quantity']); ?>
</td>
</tr>
<?php endforeach; ?>

</table>


<!-- PAYMENT SUMMARY -->
<h3 style="
background:#0f766e;
color:#fff;
padding:6px;
font-size:14px;
margin-top:20px;
">Payment Summary</h3>

<table style="width:100%; border-collapse:collapse;">

<tr>
<td style="padding:6px;">Subtotal</td>
<td style="padding:6px; text-align:right;">
<?php echo zicture_plain_money((float)($order['subtotal'] ?? 0)); ?>
</td>
</tr>

<?php if ((float)($order['discount'] ?? 0) > 0): ?>
<tr>
<td style="padding:6px;">Discount</td>
<td style="padding:6px; text-align:right; color:#dc2626;">
-<?php echo zicture_plain_money((float)$order['discount']); ?>
</td>
</tr>
<?php endif; ?>

<tr>
<td style="padding:6px;">Tax</td>
<td style="padding:6px; text-align:right;">
<?php echo zicture_plain_money((float)($order['tax'] ?? 0)); ?>
</td>
</tr>

<tr>
<td style="padding:6px;">Shipping</td>
<td style="padding:6px; text-align:right;">
<?php echo ((float)($order['shipping'] ?? 0) > 0 
    ? zicture_plain_money((float)$order['shipping']) 
    : 'FREE'); ?>
</td>
</tr>

<tr style="background:#f1f1f1; font-weight:bold;">
<td style="padding:8px;">Total Paid</td>
<td style="padding:8px; text-align:right; color:#16a34a;">
<?php echo zicture_plain_money((float)$order['total_price']); ?>
</td>
</tr>

</table>


<!-- FOOTER -->
<p style="margin-top:25px; text-align:center; color:#555;">
Thank you for shopping with <strong>Zicture</strong> 
</p>

</body>
</html>

<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');

ob_clean();
$dompdf->render();
$dompdf->stream("zicture-invoice-" . $orderId . ".pdf", ["Attachment" => true]);
exit;
?>