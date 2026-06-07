<?php
// location.php - Set delivery location
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['delivery_city'] = trim($_POST['city'] ?? '');
    $_SESSION['delivery_street'] = trim($_POST['street'] ?? '');
    $_SESSION['delivery_zipcode'] = trim($_POST['zipcode'] ?? '');
    $_SESSION['delivery_phone'] = trim($_POST['phone'] ?? '');
    $message = 'Location saved for ' . ($_SESSION['delivery_city'] ?: 'your selected city') . '.';
}

$title = 'Set Location - Zicture';
include 'header-include.php';

$cities = [
    'Dhaka' => 'Dhaka (Free Delivery)',
    'Sylhet' => 'Sylhet',
    'Savar' => 'Savar',
    'Khulna' => 'Khulna',
    'Magura' => 'Magura',
    'Cumilla' => 'Cumilla',
    'Agrabad' => 'Agrabad',
    'Chittagong' => 'Chittagong',
    'Chottogram' => 'Chottogram',
    'Other' => 'Other Location'
];
$currentCity = $_SESSION['delivery_city'] ?? '';
?>

<main class="container my-5">
    <?php if ($message): ?><div class="alert alert-success text-center"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <h1 class="display-6 fw-bold mb-2"><i class="fa-solid fa-location-dot text-primary me-2"></i>Set Your Location</h1>
            <p class="text-muted mb-4">Save delivery details for cart and checkout information.</p>

            <div class="feedback-card bg-white p-4 mb-4">
                <h5 class="fw-bold mb-3">Select Your City</h5>
                <div class="row g-3">
                    <?php foreach ($cities as $key => $city): ?>
                        <div class="col-md-6">
                            <button type="button" class="location-card card w-100 border-2 <?php echo $currentCity === $key ? 'active' : ''; ?>" data-location="<?php echo htmlspecialchars($key); ?>">
                                <div class="card-body text-center py-4">
                                    <i class="fa-solid fa-map-pin text-primary mb-2 fs-4"></i>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($city); ?></h6>
                                </div>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="summary-card bg-white p-4 sticky-lg-top" style="top: 92px;">
                <h5 class="fw-bold mb-3">Delivery Address</h5>
                <form method="POST">
                    <div class="mb-3"><label class="form-label" for="street">Street Address</label><input type="text" class="form-control" id="street" name="street" value="<?php echo htmlspecialchars($_SESSION['delivery_street'] ?? ''); ?>" required></div>
                    <div class="mb-3"><label class="form-label" for="city">City</label><input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($currentCity); ?>" required readonly></div>
                    <div class="mb-3"><label class="form-label" for="zipcode">Zip Code</label><input type="text" class="form-control" id="zipcode" name="zipcode" value="<?php echo htmlspecialchars($_SESSION['delivery_zipcode'] ?? ''); ?>"></div>
                    <div class="mb-3"><label class="form-label" for="phone">Phone Number</label><input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_SESSION['delivery_phone'] ?? ''); ?>" required></div>
                    <button type="submit" class="btn btn-success btn-lg w-100"><i class="fa-solid fa-check me-2"></i>Save Location</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.querySelectorAll('.location-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.location-card').forEach(item => item.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('city').value = this.getAttribute('data-location');
    });
});
</script>

<?php include 'footer-include.php'; ?>
