<?php
$pageTitle = "About - Esports Tournament Management System";
$basePath = "";
require_once "includes/header.php";
require_once "includes/navbar.php";
?>

<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <span class="text-cyan text-uppercase fw-bold small letter-spacing">Project Overview</span>
            <h1 class="text-white hero-title mb-3">About The <br><span class="text-cyan">Project</span></h1>
            <p class="text-muted lead">
                The Esports Tournament Management System is a comprehensive web-based platform engineered to automate and streamline the lifecycle of Battlegrounds Mobile India (BGMI) tournaments.
            </p>
            <p class="text-muted">
                Developed as a TY BSc Computer Science capstone project, this application directly addresses the vulnerabilities and overhead of organizing tournaments via WhatsApp groups, spreadsheet formulas, and manual tallying.
            </p>
        </div>
        <div class="col-lg-6">
            <div class="esports-card p-4">
                <h4 class="text-white mb-3"><i class="fa-solid fa-graduation-cap text-cyan me-2"></i>Academic Submission Details</h4>
                <table class="table table-dark table-borderless small mb-0">
                    <tr>
                        <td class="text-muted">Degree / Course:</td>
                        <td class="text-white fw-bold">TY BSc Computer Science</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Project Name:</td>
                        <td class="text-cyan fw-bold">Esports Tournament Management System</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Primary Focus:</td>
                        <td class="text-white">BGMI Competitive Tournament Automation</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Technologies:</td>
                        <td class="text-white">PHP, MySQL, HTML5, CSS3, JavaScript, Bootstrap 5</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Local Server:</td>
                        <td class="text-white">XAMPP (Apache + MariaDB/MySQL)</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Problem vs Solution Section -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="esports-card h-100 border-danger border-opacity-25">
                <h4 class="text-flame mb-3"><i class="fa-solid fa-triangle-exclamation me-2"></i>The Existing Manual System</h4>
                <ul class="text-muted small ps-3">
                    <li class="mb-2">Tournament registrations handled across chaotic WhatsApp groups.</li>
                    <li class="mb-2">Manual point calculations in Excel lead to human error and score disputes.</li>
                    <li class="mb-2">Room credentials leaked prematurely to unverified or unauthorized players.</li>
                    <li class="mb-2">Hours of delay before final leaderboards and certificates are published.</li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="esports-card h-100 border-info border-opacity-25">
                <h4 class="text-cyan mb-3"><i class="fa-solid fa-circle-check me-2"></i>Our Proposed Centralized System</h4>
                <ul class="text-muted small ps-3">
                    <li class="mb-2">Centralized portal for player verification, squad rosters, and sign-ups.</li>
                    <li class="mb-2">Automated BGMI point engine calculates placement + kill points instantly.</li>
                    <li class="mb-2">Secure access-controlled release of Room ID and Passwords to approved squads.</li>
                    <li class="mb-2">Real-time dynamic leaderboards with automatic tie-breakers and downloadable e-certificates.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
