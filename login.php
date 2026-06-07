<?php
// login.php - Demo session login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $name = $email !== '' ? explode('@', $email)[0] : 'Guest Shopper';
    }
    $_SESSION['customer_name'] = ucwords(str_replace(['.', '_', '-'], ' ', $name));
    $_SESSION['customer_email'] = $email;
    if (!empty($_FILES['avatar']['name']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime = mime_content_type($_FILES['avatar']['tmp_name']);
        if (isset($allowedTypes[$mime])) {
            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR . 'profiles';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $filename = 'profile-' . session_id() . '-' . time() . '.' . $allowedTypes[$mime];
            $target = $uploadDir . DIRECTORY_SEPARATOR . $filename;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                $_SESSION['customer_avatar'] = './photo/profiles/' . $filename;
            }
        }
    }
    $message = 'Login saved. You can continue shopping as ' . $_SESSION['customer_name'] . '.';
}

if (isset($_GET['logout'])) {
    unset($_SESSION['customer_name'], $_SESSION['customer_email'], $_SESSION['customer_avatar']);
    header('Location: login.php');
    exit;
}

$title = 'Login - Zicture';
include 'header-include.php';
?>

<main class="login-page">
    <div class="container">
    <div class="row justify-content-center align-items-center g-4">
        <div class="col-lg-5">
            <div class="login-welcome">
                <p class="text-primary fw-bold mb-2">Welcome back</p>
                <h1>Shop faster with your Zicture profile</h1>
                <p>Save your display name, profile picture, location, cart, wishlist, and invoice flow in one responsive session.</p>
                <div class="login-feature-grid">
                    <span><i class="fa-solid fa-user-check"></i> Profile shown site-wide</span>
                    <span><i class="fa-solid fa-file-pdf"></i> Invoice download</span>
                    <span><i class="fa-solid fa-tags"></i> Coupon ready</span>
                    <span><i class="fa-solid fa-mobile-screen"></i> Mobile friendly</span>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <?php if ($message): ?><div class="alert alert-success text-center"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <div class="feedback-card bg-white overflow-hidden">
                <div class="p-4 text-white" style="background: linear-gradient(135deg, #175cd3, #0f766e);">
                    <h1 class="h3 text-center mb-0"><i class="fa-solid fa-user-circle me-2"></i>Login</h1>
                </div>
                <div class="p-4">
                    <?php if (!empty($_SESSION['customer_name'])): ?>
                        <div class="profile-card text-center">
                            <?php if (zicture_customer_avatar()): ?>
                                <img class="profile-preview" src="<?php echo htmlspecialchars(zicture_customer_avatar()); ?>" alt="<?php echo htmlspecialchars($_SESSION['customer_name']); ?>">
                            <?php else: ?>
                                <div class="profile-preview profile-preview-initials"><?php echo htmlspecialchars(zicture_customer_initials()); ?></div>
                            <?php endif; ?>
                            <h2 class="h4 fw-bold mb-1"><?php echo htmlspecialchars($_SESSION['customer_name']); ?></h2>
                            <p class="text-muted mb-4"><?php echo !empty($_SESSION['customer_email']) ? htmlspecialchars($_SESSION['customer_email']) : 'No email saved'; ?></p>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="products.php?category=all" class="btn btn-primary btn-lg">Continue Shopping</a>
                            <?php if (!empty($_SESSION['last_order_id'])): ?><a href="invoice.php?order_id=<?php echo (int)$_SESSION['last_order_id']; ?>" class="btn btn-outline-success">Download Last Invoice</a><?php endif; ?>
                            <a href="login.php?logout=1" class="btn btn-outline-danger">Logout Demo Session</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="Your name">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="demo@zicture.com" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Any demo password" required>
                            </div>
                            <div class="mb-3">
                                <label for="avatar" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control form-control-lg" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif">
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3"><i class="fa-solid fa-sign-in-alt me-2"></i>Login</button>
                            <div class="alert alert-info small mb-0">Demo login stores your name in this browsing session. No real account table is required.</div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>
</main>

<?php include 'footer-include.php'; ?>
