<?php
$base = isset($basePath) ? $basePath : '';
?>
<footer class="footer-custom mt-auto">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-gamepad text-cyan fs-4"></i>
                    <h5 class="mb-0 text-white brand-font">ESPORTS<span class="text-cyan">HUB</span></h5>
                </div>
                <p class="text-muted small">
                    A centralized Esports Tournament Management Platform designed for competitive BGMI college & community events. Automated points calculation, instant leaderboards, and verified participation certificates.
                </p>
                <div class="d-flex gap-3 text-cyan">
                    <a href="#" class="text-muted hover-cyan"><i class="fa-brands fa-discord fs-5"></i></a>
                    <a href="#" class="text-muted hover-cyan"><i class="fa-brands fa-youtube fs-5"></i></a>
                    <a href="#" class="text-muted hover-cyan"><i class="fa-brands fa-instagram fs-5"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6 class="text-white brand-font mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="<?php echo $base; ?>index.php" class="footer-link small">Home</a></li>
                    <li><a href="<?php echo $base; ?>tournaments/index.php" class="footer-link small">Tournaments</a></li>
                    <li><a href="<?php echo $base; ?>about.php" class="footer-link small">About Project</a></li>
                    <li><a href="<?php echo $base; ?>contact.php" class="footer-link small">Support & Help</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-white brand-font mb-3">Tournament Features</h6>
                <ul class="list-unstyled">
                    <li><span class="text-muted small"><i class="fa-solid fa-check text-cyan me-2"></i>Automated BGMI Scoring</span></li>
                    <li><span class="text-muted small"><i class="fa-solid fa-check text-cyan me-2"></i>Custom Room Management</span></li>
                    <li><span class="text-muted small"><i class="fa-solid fa-check text-cyan me-2"></i>Live Leaderboards</span></li>
                    <li><span class="text-muted small"><i class="fa-solid fa-check text-cyan me-2"></i>E-Certificates</span></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-white brand-font mb-3">College Project Info</h6>
                <p class="text-muted small mb-1"><strong>Course:</strong> TY BSc Computer Science</p>
                <p class="text-muted small mb-1"><strong>Game:</strong> Battlegrounds Mobile India</p>
                <p class="text-muted small"><strong>Stack:</strong> PHP, MySQL, Bootstrap 5</p>
            </div>
        </div>

        <hr class="border-secondary my-4 opacity-25">

        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="text-muted small mb-0">&copy; <?php echo date('Y'); ?> Esports Tournament Management System. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                <span class="badge bg-dark border border-secondary text-muted">Version 1.0</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 Bundle JS (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo $base; ?>assets/js/main.js"></script>
</body>
</html>
