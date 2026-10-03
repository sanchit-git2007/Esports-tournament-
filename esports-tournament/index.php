<?php
$pageTitle = "Home - Esports Tournament Management System";
$basePath = "";
require_once "includes/header.php";
require_once "includes/navbar.php";
?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container">
        <div class="hero-badge">
            <i class="fa-solid fa-bolt"></i> Battlegrounds Mobile India Championship Platform
        </div>
        <h1 class="hero-title text-white">
            ESPORTS TOURNAMENT <br>
            <span class="text-cyan">MANAGEMENT SYSTEM</span>
        </h1>
        <p class="hero-subtitle mx-auto">
            Organize. Compete. Track. Win. The centralized tournament portal built for college & community BGMI esports events with real-time automatic scoring and instant leaderboards.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a href="tournaments/index.php" class="btn btn-neon-primary btn-lg">
                <i class="fa-solid fa-trophy me-2"></i> Explore Tournaments
            </a>
            <a href="register.php" class="btn btn-neon-outline btn-lg">
                <i class="fa-solid fa-user-plus me-2"></i> Register Now
            </a>
        </div>

        <!-- Quick Stats Bar -->
        <div class="row g-3 justify-content-center mt-5">
            <div class="col-6 col-md-3">
                <div class="p-3 rounded bg-dark border border-secondary border-opacity-25">
                    <h3 class="text-cyan mb-0">100%</h3>
                    <small class="text-muted text-uppercase">Automated Points</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded bg-dark border border-secondary border-opacity-25">
                    <h3 class="text-flame mb-0">BGMI</h3>
                    <small class="text-muted text-uppercase">Custom Scoring</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded bg-dark border border-secondary border-opacity-25">
                    <h3 class="text-gold mb-0">Live</h3>
                    <small class="text-muted text-uppercase">Instant Standings</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded bg-dark border border-secondary border-opacity-25">
                    <h3 class="text-white mb-0">E-Cert</h3>
                    <small class="text-muted text-uppercase">Auto Generation</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured / Upcoming Tournaments Section -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <span class="text-cyan text-uppercase fw-bold small letter-spacing">Compete & Conquer</span>
                <h2 class="text-white mb-0">Featured Tournaments</h2>
            </div>
            <a href="tournaments/index.php" class="btn btn-neon-outline btn-sm">
                View All <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <!-- Sample Card 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="esports-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge-custom badge-open">Registration Open</span>
                            <span class="badge bg-dark text-cyan border border-info border-opacity-25">Public</span>
                        </div>
                        <h4 class="text-white mb-2">SIES BGMI Championship 2026</h4>
                        <p class="text-muted small mb-3">Inter-college battle royale showdown. Erangel & Miramar battle across 4 intense rounds.</p>
                        
                        <div class="bg-dark p-3 rounded mb-3 border border-secondary border-opacity-10 small">
                            <div class="d-flex justify-content-between text-muted mb-1">
                                <span><i class="fa-solid fa-gamepad text-cyan me-2"></i>Game:</span>
                                <strong class="text-white">BGMI (Squad)</strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted mb-1">
                                <span><i class="fa-solid fa-users text-cyan me-2"></i>Slots:</span>
                                <strong class="text-white">16 Teams</strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span><i class="fa-solid fa-calendar-days text-cyan me-2"></i>Date:</span>
                                <strong class="text-white">Aug 30, 2026</strong>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="tournaments/details.php?id=1" class="btn btn-neon-primary w-100 btn-sm">
                            <i class="fa-solid fa-circle-info me-1"></i> View Details & Register
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sample Card 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="esports-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge-custom badge-upcoming">Upcoming</span>
                            <span class="badge bg-dark text-warning border border-warning border-opacity-25"><i class="fa-solid fa-lock me-1"></i>Private</span>
                        </div>
                        <h4 class="text-white mb-2">Campus Clash BGMI League</h4>
                        <p class="text-muted small mb-3">Exclusive invitational championship for verified department squads with access passcode.</p>
                        
                        <div class="bg-dark p-3 rounded mb-3 border border-secondary border-opacity-10 small">
                            <div class="d-flex justify-content-between text-muted mb-1">
                                <span><i class="fa-solid fa-gamepad text-cyan me-2"></i>Game:</span>
                                <strong class="text-white">BGMI (Squad)</strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted mb-1">
                                <span><i class="fa-solid fa-users text-cyan me-2"></i>Slots:</span>
                                <strong class="text-white">20 Teams</strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span><i class="fa-solid fa-calendar-days text-cyan me-2"></i>Date:</span>
                                <strong class="text-white">Sep 05, 2026</strong>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="tournaments/details.php?id=2" class="btn btn-neon-outline w-100 btn-sm">
                            <i class="fa-solid fa-key me-1"></i> Access Private Match
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sample Card 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="esports-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge-custom badge-completed">Completed</span>
                            <span class="badge bg-dark text-cyan border border-info border-opacity-25">Public</span>
                        </div>
                        <h4 class="text-white mb-2">Monsoon BGMI Masters</h4>
                        <p class="text-muted small mb-3">High octane tournament concluded with Team Alpha lifting the championship trophy.</p>
                        
                        <div class="bg-dark p-3 rounded mb-3 border border-secondary border-opacity-10 small">
                            <div class="d-flex justify-content-between text-muted mb-1">
                                <span><i class="fa-solid fa-gamepad text-cyan me-2"></i>Game:</span>
                                <strong class="text-white">BGMI (Squad)</strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted mb-1">
                                <span><i class="fa-solid fa-trophy text-gold me-2"></i>Winner:</span>
                                <strong class="text-gold">Team Alpha (64 Pts)</strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span><i class="fa-solid fa-certificate text-cyan me-2"></i>Certificates:</span>
                                <strong class="text-success">Issued</strong>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="tournaments/details.php?id=3" class="btn btn-outline-secondary w-100 btn-sm">
                            <i class="fa-solid fa-ranking-star me-1"></i> View Leaderboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Key Platform Features -->
<section class="py-5 bg-dark border-top border-bottom border-secondary border-opacity-10">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="text-cyan text-uppercase fw-bold small letter-spacing">Built for Esports</span>
            <h2 class="text-white">Powerful Tournament Features</h2>
            <p class="text-muted">Designed specifically to eliminate WhatsApp and Excel chaos with fully integrated tournament workflows.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="esports-card h-100">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <h4 class="text-white">Team & Roster Management</h4>
                    <p class="text-muted small">Captains can assemble rosters, register players, and manage squad details with full verification.</p>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="esports-card h-100">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h4 class="text-white">Public & Private Tournaments</h4>
                    <p class="text-muted small">Host open registrations or exclusive departmental tournaments protected by secure access codes.</p>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="esports-card h-100">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                    <h4 class="text-white">Automated BGMI Scoring</h4>
                    <p class="text-muted small">Admins enter placement and kill counts. Placement points + kill points are computed automatically in real-time.</p>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="esports-card h-100">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <h4 class="text-white">Custom Room Management</h4>
                    <p class="text-muted small">Release Room ID and Passwords securely to verified and approved teams prior to match start.</p>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="esports-card h-100">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-ranking-star"></i>
                    </div>
                    <h4 class="text-white">Live Leaderboards</h4>
                    <p class="text-muted small">Dynamic tournament standings sorted by total points, kill points, and placement tie-breakers.</p>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="esports-card h-100">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-award"></i>
                    </div>
                    <h4 class="text-white">Automated Certificates</h4>
                    <p class="text-muted small">Instant generation of digital Participation & Winner certificates ready for download and print.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works (7 Step Process) -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-cyan text-uppercase fw-bold small letter-spacing">Step-by-Step Flow</span>
            <h2 class="text-white">How The Platform Works</h2>
            <p class="text-muted">From team sign-up to championship certificate generation in 7 seamless stages.</p>
        </div>

        <div class="row g-3 justify-content-center">
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <i class="fa-solid fa-user-check text-cyan fs-3 mb-2 mt-2"></i>
                    <h6 class="text-white">Register & Login</h6>
                    <p class="text-muted small mb-0">Create player account with secure credentials.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <i class="fa-solid fa-people-group text-cyan fs-3 mb-2 mt-2"></i>
                    <h6 class="text-white">Create Squad</h6>
                    <p class="text-muted small mb-0">Assemble 4-player BGMI team with a captain.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <i class="fa-solid fa-trophy text-cyan fs-3 mb-2 mt-2"></i>
                    <h6 class="text-white">Join Tournament</h6>
                    <p class="text-muted small mb-0">Select tournament, enter access code if private.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="step-card">
                    <div class="step-number">4</div>
                    <i class="fa-solid fa-gamepad text-cyan fs-3 mb-2 mt-2"></i>
                    <h6 class="text-white">Play Matches</h6>
                    <p class="text-muted small mb-0">Access Room ID & Password to join custom match.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="step-card">
                    <div class="step-number">5</div>
                    <i class="fa-solid fa-square-poll-vertical text-cyan fs-3 mb-2 mt-2"></i>
                    <h6 class="text-white">Results Entry</h6>
                    <p class="text-muted small mb-0">Admin records kills & placement for every squad.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="step-card">
                    <div class="step-number">6</div>
                    <i class="fa-solid fa-chart-line text-cyan fs-3 mb-2 mt-2"></i>
                    <h6 class="text-white">Auto Leaderboard</h6>
                    <p class="text-muted small mb-0">Total points calculate automatically & rank teams.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="step-card">
                    <div class="step-number">7</div>
                    <i class="fa-solid fa-medal text-gold fs-3 mb-2 mt-2"></i>
                    <h6 class="text-gold">Claim Certificates</h6>
                    <p class="text-muted small mb-0">Winners & participants download verified e-certificates.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 bg-gradient">
    <div class="container">
        <div class="esports-card text-center p-5 border-cyan">
            <h2 class="text-white mb-3">Ready to Host or Compete in the Next Championship?</h2>
            <p class="text-muted mx-auto max-w-600 mb-4">
                Join our gaming community today. Create your squad, register for upcoming BGMI tournaments, and track your climb to the top of the leaderboard!
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="register.php" class="btn btn-neon-primary btn-lg">Create Free Account</a>
                <a href="about.php" class="btn btn-neon-outline btn-lg">Learn More</a>
            </div>
        </div>
    </div>
</section>

<?php require_once "includes/footer.php"; ?>
