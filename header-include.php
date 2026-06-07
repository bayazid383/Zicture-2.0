<?php
// header-include.php - Consistent responsive header for all pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

require_once 'db.php';
require_once 'helpers.php';

$pageTitle = isset($title) ? $title : 'Zicture - The Online Shopping';
$cartCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE session_id = ?");
    $stmt->execute([$_SESSION['session_id']]);
    $result = $stmt->fetch();
    $cartCount = (int)($result['total'] ?? 0);
} catch (Exception $e) {
    $cartCount = 0;
}

$customerName = $_SESSION['customer_name'] ?? '';
$customerAvatar = zicture_customer_avatar();
$locationCity = $_SESSION['delivery_city'] ?? '';
$currentCurrency = zicture_currency();
$departmentCategories = zicture_categories();
unset($departmentCategories['all']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/92a6706205.js" crossorigin="anonymous"></script>
</head>
<body>
<header class="site-header">
    <div class="brand-bar">
        <div class="container-fluid px-lg-4">
            <div class="brand-row">
                <a class="brand-lockup" href="index.php" aria-label="Zicture home">
                    <img src="./photo/Z_Energy_logo.png" alt="Zicture Logo" class="brand-logo">
                    <span class="brand-copy">
                        <strong>Zicture</strong>
                        <span>The Online Shopping</span>
                    </span>
                </a>

                <form class="header-search" action="products.php" method="GET">
                    <input type="hidden" name="category" value="all">
                    <input class="form-control" type="search" name="q" id="headerSearch" placeholder="Search products by name, category, or description..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    <button class="btn btn-warning" type="submit" aria-label="Search"><i class="fa-solid fa-search"></i></button>
                </form>

                <nav class="quick-actions" aria-label="Account and shopping links">
                    <form method="POST" class="currency-form">
                        <select class="form-select form-select-sm" name="currency_select" onchange="this.form.submit()" aria-label="Currency">
                            <option value="BDT" <?php echo $currentCurrency === 'BDT' ? 'selected' : ''; ?>>BD Tk</option>
                            <option value="CNY" <?php echo $currentCurrency === 'CNY' ? 'selected' : ''; ?>>CN Yuan</option>
                            <option value="USD" <?php echo $currentCurrency === 'USD' ? 'selected' : ''; ?>>US Dollar</option>
                        </select>
                    </form>
                    <a href="login.php" class="btn btn-sm btn-outline-light account-chip">
                        <?php if ($customerAvatar): ?>
                            <img src="<?php echo htmlspecialchars($customerAvatar); ?>" alt="<?php echo htmlspecialchars($customerName ?: 'Profile'); ?>">
                        <?php else: ?>
                            <span class="profile-initials"><?php echo htmlspecialchars(zicture_customer_initials()); ?></span>
                        <?php endif; ?>
                        <span><?php echo $customerName ? htmlspecialchars($customerName) : 'Login'; ?></span>
                    </a>
                    <a href="location.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-location-dot"></i><span><?php echo $locationCity ? htmlspecialchars($locationCity) : 'Location'; ?></span></a>
                    <a href="wishlist.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-heart"></i><span>Wishlist</span></a>
                    <a href="cart.php" class="btn btn-sm btn-outline-light position-relative"><i class="fa-solid fa-shopping-cart"></i><span>Cart</span><span id="cartBadge" class="cart-badge <?php echo $cartCount > 0 ? '' : 'd-none'; ?>"><?php echo $cartCount; ?></span></a>
                </nav>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-xl navbar-light bg-warning sticky-top main-nav">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand fw-bold d-xl-none" href="index.php">Zicture Menu</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav nav-fill w-100 align-items-xl-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="departmentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Department</a>
                        <ul class="dropdown-menu">
                            <?php foreach ($departmentCategories as $key => $meta): ?>
                                <li><a class="dropdown-item" href="products.php?category=<?php echo urlencode($key); ?>"><i class="fa-solid fa-<?php echo htmlspecialchars($meta['icon']); ?> me-2"></i><?php echo htmlspecialchars($meta['label']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="index.php#trending">Trending On</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#upcoming">Upcoming</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php?category=all&sort=popular">Popular</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php?category=all">Shop</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php#support-center">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php?category=all">All Item</a></li>
                    <li class="nav-item"><a class="nav-link" href="product-book.php">ProductBook</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin.php">Admin</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
