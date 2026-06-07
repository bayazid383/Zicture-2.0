<?php
// contact.php - Contact page

$title = 'Contact Us - Zicture';
?>
<?php include 'header-include.php'; ?>

<main class="container my-5" id="support-center">
    <h1 class="mb-4">
        <i class="fa-solid fa-envelope text-primary"></i> Contact Us
    </h1>
    <hr>

    <div class="row">
        <!-- Contact Info -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fa-solid fa-phone text-primary me-2"></i> Phone
                    </h5>
                    <p class="card-text">
                        <strong>Main:</strong> +880 1798-070234<br>
                        <strong>Support:</strong> +880 1853-079398<br>
                        <strong>Hours:</strong> 9 AM - 9 PM (Daily)
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fa-solid fa-envelope text-success me-2"></i> Email
                    </h5>
                    <p class="card-text">
                        <a href="mailto:bayazidh383@gmail.com" class="text-decoration-none">
                            bayazidh383@gmail.com
                        </a><br>
                        We reply within 24 hours
                    </p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fa-solid fa-location-dot text-danger me-2"></i> Location
                    </h5>
                    <p class="card-text">
                        <strong>Zicture - The Online Shopping</strong><br>
                        Dhaka, Bangladesh<br>
                        Available nationwide for delivery
                    </p>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Send us a Message</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="submit-feedback.php">
                        <div class="mb-3">
                            <label for="fname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="fname" name="fname" required>
                        </div>
                        <div class="mb-3">
                            <label for="lname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lname" name="lname">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" placeholder="How can we help?" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-paper-plane me-2"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="mb-4">Frequently Asked Questions</h3>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">What are your delivery charges?</h6>
                </div>
                <div class="card-body">
                    <p>We offer <strong>FREE DELIVERY</strong> for orders above 500 Tk in Dhaka and selected areas.</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">How do I track my order?</h6>
                </div>
                <div class="card-body">
                    <p>You'll receive a tracking number via email and SMS after your order is confirmed.</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">What's your return policy?</h6>
                </div>
                <div class="card-body">
                    <p>We offer 7 days easy return policy on most items in original condition.</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Do you have a physical store?</h6>
                </div>
                <div class="card-body">
                    <p>We operate online only for now. Call us to place orders by phone.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer-include.php'; ?>
