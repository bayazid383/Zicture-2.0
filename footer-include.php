<?php
// footer-include.php - Consistent footer and shared UI helpers
?>
<footer class="bg-dark text-white mt-5 pt-5 pb-3" id="support-footer">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <h5 class="fw-bold text-warning mb-3">Zicture</h5>
                <p class="text-white-50 mb-3">Your one-stop online shopping destination for quality products, hot deals, daily picks, and fast delivery.</p>
                <div class="d-flex gap-3 fs-5">
                    <a href="contact.php#support-center" class="text-warning"><i class="fa-brands fa-facebook"></i></a>
                    <a href="contact.php#support-center" class="text-warning"><i class="fa-brands fa-twitter"></i></a>
                    <a href="contact.php#support-center" class="text-warning"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
            <div class="col-md-3">
                <h5 class="fw-bold text-warning mb-3">Quick Links</h5>
                <a class="footer-link" href="index.php">Home</a><br>
                <a class="footer-link" href="products.php?category=all">All Item</a><br>
                <a class="footer-link" href="product-book.php">ProductBook</a><br>
                <a class="footer-link" href="wishlist.php">Wishlist</a><br>
                <a class="footer-link" href="compare.php">Compare Products</a><br>
                <a class="footer-link" href="cart.php">Shopping Cart</a>
            </div>
            <div class="col-md-3">
                <h5 class="fw-bold text-warning mb-3">Services</h5>
                <a class="footer-link" href="index.php#daily-section"><i class="fa-solid fa-calendar-day text-success me-2"></i>Daily Section</a><br>
                <a class="footer-link" href="location.php"><i class="fa-solid fa-truck-fast text-success me-2"></i>Fast Delivery</a><br>
                <a class="footer-link" href="cart.php"><i class="fa-solid fa-cart-shopping text-success me-2"></i>Easy Cart</a><br>
                <a class="footer-link" href="contact.php#support-center"><i class="fa-solid fa-headset text-success me-2"></i>Support Center</a><br>
                <a class="footer-link" href="index.php#coupon-section"><i class="fa-solid fa-tags text-success me-2"></i>Coupon Discount</a>
            </div>
            <div class="col-md-3" id="support-center-footer">
                <h5 class="fw-bold text-warning mb-3">Support Center</h5>
                <p class="mb-2"><i class="fa-solid fa-phone text-warning me-2"></i>+880 1798-070234</p>
                <p class="mb-2"><i class="fa-solid fa-phone text-warning me-2"></i>+880 1853-079398</p>
                <p class="mb-2 no-underline"><i class="fa-solid fa-envelope text-warning me-2"></i><span>bayazidh383@gmail.com</span></p>
                <p class="mb-0"><i class="fa-solid fa-location-dot text-warning me-2"></i>Dhaka, Bangladesh</p>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="row text-center text-md-start align-items-center small text-white-50">
            <div class="col-md-6 mb-2 mb-md-0">&copy; 2022 <strong>Zicture - The Online Shopping</strong>. All Rights Reserved.</div>
            <div class="col-md-6 text-md-end">Designed & Developed by <strong>MD Bayazid Hossain</strong></div>
        </div>
    </div>
</footer>

<div class="modal fade" id="zictureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white border-0" id="zictureModalHeader">
                <h5 class="modal-title" id="zictureModalTitle">Zicture</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="icon-tile mx-auto mb-3" id="zictureModalIcon"><i class="fa-solid fa-check"></i></div>
                <h4 id="zictureModalMessage" class="mb-2">Done</h4>
                <p id="zictureModalDetail" class="text-muted mb-0"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center flex-wrap" id="zictureModalActions">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Continue Shopping</button>
            </div>
        </div>
    </div>
</div>

<script>
function showZictureModal(options) {
    const settings = Object.assign({ title: 'Zicture', message: 'Done', detail: '', icon: 'fa-check', color: '#14804a', actions: '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Continue Shopping</button>' }, options || {});
    document.getElementById('zictureModalHeader').style.background = settings.color;
    document.getElementById('zictureModalTitle').textContent = settings.title;
    document.getElementById('zictureModalMessage').textContent = settings.message;
    document.getElementById('zictureModalDetail').textContent = settings.detail;
    document.getElementById('zictureModalIcon').style.color = settings.color;
    document.getElementById('zictureModalIcon').innerHTML = '<i class="fa-solid ' + settings.icon + '"></i>';
    document.getElementById('zictureModalActions').innerHTML = settings.actions;
    new bootstrap.Modal(document.getElementById('zictureModal')).show();
}
function updateCartBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    badge.textContent = count;
    badge.classList.toggle('d-none', Number(count) <= 0);
}
function zictureAddToCart(productId, productName, goToCart) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    return fetch('add-to-cart.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Could not add product');
            updateCartBadge(data.cartCount || 0);
            if (goToCart) { window.location.href = 'cart.php'; return data; }
            showZictureModal({ title: 'Added to Cart', message: productName || data.product.name, detail: 'Your cart now has ' + data.cartCount + ' item(s).', icon: 'fa-cart-shopping', color: '#14804a', actions: '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Continue Shopping</button><a href="cart.php" class="btn btn-success">View Cart</a>' });
            return data;
        })
        .catch(error => showZictureModal({ title: 'Cart Error', message: error.message, icon: 'fa-triangle-exclamation', color: '#dc3545' }));
}
function zictureBuyNow(productId, productName) { zictureAddToCart(productId, productName, true); }
function zictureWishlist(productId, productName, action, wishId) {
    const formData = new FormData();
    formData.append('action', action || 'add');
    if (productId) formData.append('product_id', productId);
    if (wishId) formData.append('wish_id', wishId);
    fetch('wishlist-action.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Wishlist action failed');
            showZictureModal({ title: action === 'remove' ? 'Wishlist Updated' : 'Saved to Wishlist', message: productName || data.message, detail: data.message, icon: action === 'remove' ? 'fa-heart-crack' : 'fa-heart', color: action === 'remove' ? '#dc3545' : '#d63384', actions: '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><a href="wishlist.php" class="btn btn-danger">View Wishlist</a>' });
            if (action === 'remove') setTimeout(() => window.location.reload(), 700);
        })
        .catch(error => showZictureModal({ title: 'Wishlist Error', message: error.message, icon: 'fa-triangle-exclamation', color: '#dc3545' }));
}
function zictureCompare(productId, productName) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    fetch('compare-action.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Compare action failed');
            showZictureModal({ title: 'Added to Compare', message: productName || data.message, detail: 'You are comparing ' + data.count + ' of 4 products.', icon: 'fa-right-left', color: '#175cd3', actions: '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Browsing</button><a href="compare.php" class="btn btn-primary">Compare Now</a>' });
        })
        .catch(error => showZictureModal({ title: 'Compare Error', message: error.message, icon: 'fa-triangle-exclamation', color: '#dc3545' }));
}
function copyCoupon(code) {
    if (navigator.clipboard) navigator.clipboard.writeText(code).catch(() => {});
    const input = document.getElementById('promo_code');
    if (input) input.value = code;
    showZictureModal({ title: 'Coupon Ready', message: code, detail: 'Use this code in your cart for 20% off.', icon: 'fa-tags', color: '#7c3aed', actions: '<a href="cart.php" class="btn btn-primary">Apply in Cart</a><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>' });
}
function showDeliveryInfo() {
    showZictureModal({ title: 'Fast Delivery', message: 'Free delivery over Tk 1000', detail: 'Set your city in Location. Dhaka orders over Tk 1000 get free delivery.', icon: 'fa-truck-fast', color: '#0f766e', actions: '<a href="location.php" class="btn btn-success">Set Location</a><a href="cart.php" class="btn btn-outline-primary">Open Cart</a>' });
}
</script>
</body>
</html>
