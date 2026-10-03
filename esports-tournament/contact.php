<?php
$pageTitle = "Contact & Support - Esports Tournament Management System";
$basePath = "";
$messageSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($name && $email && $message) {
        $messageSent = true;
    }
}

require_once "includes/header.php";
require_once "includes/navbar.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <span class="text-cyan text-uppercase fw-bold small letter-spacing">Help & Support</span>
                <h1 class="text-white hero-title">Get In <span class="text-cyan">Touch</span></h1>
                <p class="text-muted">Have a query regarding tournament registrations, custom rooms, or college collaborations?</p>
            </div>

            <?php if ($messageSent): ?>
                <div class="alert alert-success bg-success bg-opacity-25 text-white border-success alert-auto-dismiss mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> Thank you! Your support ticket has been received. We will get back to you shortly.
                </div>
            <?php endif; ?>

            <div class="esports-card p-4 p-md-5">
                <form action="contact.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-custom">Full Name</label>
                            <input type="text" name="name" class="form-control form-control-custom" placeholder="e.g. Sanchit More" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-custom" placeholder="player@example.com" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label-custom">Subject</label>
                            <input type="text" name="subject" class="form-control form-control-custom" placeholder="Tournament Query / Bug Report / General" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label-custom">Message</label>
                            <textarea name="message" rows="4" class="form-control form-control-custom" placeholder="Describe your query in detail..." required></textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-neon-primary w-100">
                                <i class="fa-solid fa-paper-plane me-2"></i> Send Message
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
