<?php
// =======================================================
// File: view_certificate.php
// Purpose: Printable Official E-Certificate with PDF Print Stylesheet
// =======================================================

require_once "config/database.php";

$certNumber = trim($_GET['num'] ?? '');
$certId = (int)($_GET['id'] ?? 0);

if (empty($certNumber) && $certId <= 0) {
    die("Invalid Certificate Link.");
}

// Fetch Certificate Record with full relational details
if (!empty($certNumber)) {
    $stmt = $pdo->prepare("
        SELECT c.*, t.tournament_name, t.game, tm.team_name, tm.team_tag, u.name AS captain_name, admin_u.name AS organizer_name
        FROM certificates c
        JOIN tournaments t ON c.tournament_id = t.id
        JOIN teams tm ON c.team_id = tm.id
        JOIN users u ON tm.captain_id = u.id
        LEFT JOIN users admin_u ON t.created_by = admin_u.id
        WHERE c.certificate_number = ?
    ");
    $stmt->execute([$certNumber]);
} else {
    $stmt = $pdo->prepare("
        SELECT c.*, t.tournament_name, t.game, tm.team_name, tm.team_tag, u.name AS captain_name, admin_u.name AS organizer_name
        FROM certificates c
        JOIN tournaments t ON c.tournament_id = t.id
        JOIN teams tm ON c.team_id = tm.id
        JOIN users u ON tm.captain_id = u.id
        LEFT JOIN users admin_u ON t.created_by = admin_u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$certId]);
}

$cert = $stmt->fetch();

if (!$cert) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px; background:#0a0e17; color:#fff;'>
        <h2>Certificate Not Found</h2>
        <p>No valid verified esports certificate was found for this code.</p>
    </div>");
}

$isWinner = ($cert['certificate_type'] === 'winner');
$isRunnerUp = ($cert['certificate_type'] === 'runner_up');
$titleText = "CERTIFICATE OF PARTICIPATION";
if ($isWinner) $titleText = "CERTIFICATE OF ACHIEVEMENT";
elseif ($isRunnerUp) $titleText = "CERTIFICATE OF EXCELLENCE";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cert['certificate_number']); ?> | Official E-Certificate</title>
    
    <!-- Google Fonts: Rajdhani & Cinzel / Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Inter:wght@400;600;700&family=Rajdhani:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #05080e;
            font-family: 'Inter', sans-serif;
            color: #f1f5f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            min-height: 100vh;
        }

        /* Top Action Bar */
        .action-bar {
            width: 100%;
            max-width: 900px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: #121826;
            padding: 12px 20px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-print {
            background: linear-gradient(135deg, #00f0ff 0%, #00a3ff 100%);
            color: #000;
            border: none;
            padding: 8px 20px;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-print:hover {
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.5);
            transform: translateY(-1px);
        }

        /* Certificate Container (A4 Landscape Proportions) */
        .certificate-wrapper {
            width: 900px;
            height: 636px; /* Standard A4 landscape ratio */
            background: #0d121f;
            border: 4px solid #ffb800;
            border-radius: 12px;
            position: relative;
            padding: 30px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.8), 0 0 30px rgba(255, 184, 0, 0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Inner Ornate Border */
        .certificate-inner {
            border: 1.5px solid rgba(255, 184, 0, 0.4);
            border-radius: 8px;
            height: 100%;
            padding: 25px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            position: relative;
            background: radial-gradient(circle at center, rgba(0, 240, 255, 0.03) 0%, transparent 70%);
        }

        /* Corner Decorative Elements */
        .corner-accent {
            position: absolute;
            width: 24px;
            height: 24px;
            border: 3px solid #ffb800;
        }
        .corner-tl { top: -2px; left: -2px; border-right: none; border-bottom: none; }
        .corner-tr { top: -2px; right: -2px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: -2px; left: -2px; border-right: none; border-top: none; }
        .corner-br { bottom: -2px; right: -2px; border-left: none; border-top: none; }

        /* Certificate Typography */
        .cert-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 5px;
        }

        .cert-header h4 {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.1rem;
            color: #00f0ff;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 2.2rem;
            font-weight: 900;
            color: <?php echo $isWinner ? '#ffb800' : ($isRunnerUp ? '#e2e8f0' : '#00f0ff'); ?>;
            letter-spacing: 2px;
            margin-bottom: 10px;
            text-transform: uppercase;
            text-shadow: 0 0 20px <?php echo $isWinner ? 'rgba(255, 184, 0, 0.4)' : 'rgba(0, 240, 255, 0.3)'; ?>;
        }

        .cert-subtitle {
            font-size: 0.95rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .recipient-name {
            font-family: 'Rajdhani', sans-serif;
            font-size: 2.8rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(0, 240, 255, 0.4);
            display: inline-block;
            padding: 0 30px 4px 30px;
            margin-bottom: 12px;
        }

        .cert-body {
            font-size: 1rem;
            line-height: 1.6;
            color: #cbd5e1;
            max-width: 720px;
            margin: 0 auto;
        }

        .cert-body strong {
            color: #00f0ff;
        }

        /* Signatures & Footer Verification */
        .cert-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 15px;
            padding-top: 15px;
        }

        .signature-box {
            text-align: center;
            width: 220px;
        }

        .sig-line {
            width: 100%;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin-bottom: 6px;
        }

        .sig-name {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            color: #ffffff;
            text-transform: uppercase;
        }

        .sig-title {
            font-size: 0.75rem;
            color: #94a3b8;
            text-transform: uppercase;
        }

        .verification-badge {
            text-align: center;
        }

        .badge-seal {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: radial-gradient(circle, #ffb800 0%, #d97706 100%);
            color: #000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto 6px auto;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 800;
            font-size: 0.75rem;
            box-shadow: 0 0 15px rgba(255, 184, 0, 0.5);
        }

        .cert-code {
            font-family: monospace;
            font-size: 0.8rem;
            color: #ffb800;
            letter-spacing: 1px;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
            .certificate-wrapper {
                box-shadow: none;
                border: 3px solid #ffb800;
                width: 100vw;
                height: 100vh;
                max-width: none;
                border-radius: 0;
            }
            @page {
                size: A4 landscape;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar (Hidden when printed) -->
    <div class="action-bar">
        <div style="font-size: 0.9rem; color: #cbd5e1;">
            <i class="fa-solid fa-shield-check text-cyan" style="color:#00f0ff; margin-right: 6px;"></i> Verified Official Esports Credential
        </div>
        <button class="btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print / Save as PDF
        </button>
    </div>

    <!-- Certificate Render Document -->
    <div class="certificate-wrapper">
        <div class="certificate-inner">
            <div class="corner-accent corner-tl"></div>
            <div class="corner-accent corner-tr"></div>
            <div class="corner-accent corner-bl"></div>
            <div class="corner-accent corner-br"></div>

            <!-- Top Header -->
            <div>
                <div class="cert-header">
                    <i class="fa-solid fa-gamepad" style="color: #00f0ff; font-size: 1.2rem;"></i>
                    <h4>ESPORTS TOURNAMENT HUB &bull; OFFICIAL RECOGNITION</h4>
                </div>
                <h1 class="cert-title"><?php echo $titleText; ?></h1>
                <p class="cert-subtitle">This certificate is officially presented to</p>
            </div>

            <!-- Recipient Squad / Captain -->
            <div>
                <div class="recipient-name">
                    <?php echo htmlspecialchars($cert['team_name']); ?>
                </div>
                <div style="font-size: 1rem; color: #ffb800; font-family: 'Rajdhani', sans-serif; font-weight: 700; text-transform: uppercase; margin-bottom: 10px;">
                    Team Tag: [<?php echo htmlspecialchars($cert['team_tag']); ?>] &bull; Captain: <?php echo htmlspecialchars($cert['captain_name']); ?>
                </div>
                <p class="cert-body">
                    For outstanding skill, teamwork, and competitive excellence displayed in the 
                    <strong><?php echo htmlspecialchars($cert['tournament_name']); ?></strong> 
                    Championship in the official <strong>Battlegrounds Mobile India (BGMI)</strong> esports division.
                </p>
            </div>

            <!-- Signatures & Verification Seal -->
            <div class="cert-footer">
                <div class="signature-box">
                    <div class="sig-line"></div>
                    <div class="sig-name"><?php echo htmlspecialchars($cert['organizer_name'] ?? 'Tournament Director'); ?></div>
                    <div class="sig-title">Tournament Director</div>
                </div>

                <div class="verification-badge">
                    <div class="badge-seal">
                        <i class="fa-solid fa-trophy" style="font-size: 1.1rem; margin-bottom: 2px;"></i>
                        <span>VERIFIED</span>
                    </div>
                    <div class="cert-code"><?php echo htmlspecialchars($cert['certificate_number']); ?></div>
                    <div style="font-size: 0.75rem; color: #94a3b8;">Issued: <?php echo date('F d, Y', strtotime($cert['issue_date'])); ?></div>
                </div>

                <div class="signature-box">
                    <div class="sig-line"></div>
                    <div class="sig-name">Prof. Project Coordinator</div>
                    <div class="sig-title">College Esports Convener</div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
