<?php
// =======================================================
// File: tournaments/register.php
// Purpose: Backend Handler for Squad Tournament Registrations
// =======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$tournamentId = isset($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : 0;
$teamId = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
$accessCode = trim($_POST['access_code'] ?? '');

if ($tournamentId <= 0 || $teamId <= 0) {
    header("Location: index.php");
    exit;
}

try {
    // 1. Verify that the logged-in user is indeed the Captain of this team
    $teamCheck = $pdo->prepare("SELECT id FROM teams WHERE id = ? AND captain_id = ?");
    $teamCheck->execute([$teamId, $userId]);
    if ($teamCheck->rowCount() === 0) {
        header("Location: details.php?id={$tournamentId}&error=not_captain");
        exit;
    }

    // 2. Fetch Tournament Details
    $tStmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
    $tStmt->execute([$tournamentId]);
    $tourney = $tStmt->fetch();

    if (!$tourney) {
        header("Location: index.php");
        exit;
    }

    // 3. Check Tournament Status
    if ($tourney['status'] !== 'registration_open') {
        header("Location: details.php?id={$tournamentId}&error=closed");
        exit;
    }

    // 4. If Private Tournament, Validate Access Passcode
    if ($tourney['tournament_type'] === 'private') {
        if (empty($accessCode) || strcasecmp($accessCode, $tourney['access_code']) !== 0) {
            header("Location: details.php?id={$tournamentId}&error=invalid_code");
            exit;
        }
    }

    // 5. Check if Team is Already Registered
    $dupCheck = $pdo->prepare("SELECT id FROM tournament_registrations WHERE tournament_id = ? AND team_id = ?");
    $dupCheck->execute([$tournamentId, $teamId]);
    if ($dupCheck->rowCount() > 0) {
        header("Location: details.php?id={$tournamentId}&error=already_registered");
        exit;
    }

    // 6. Check if Maximum Team Slots are Full
    $slotCheck = $pdo->prepare("SELECT COUNT(*) FROM tournament_registrations WHERE tournament_id = ? AND status = 'approved'");
    $slotCheck->execute([$tournamentId]);
    $approvedCount = $slotCheck->fetchColumn();

    if ($approvedCount >= $tourney['max_teams']) {
        header("Location: details.php?id={$tournamentId}&error=full");
        exit;
    }

    // 7. Insert Pending Registration
    $insertStmt = $pdo->prepare("
        INSERT INTO tournament_registrations (tournament_id, team_id, status, registered_at) 
        VALUES (?, ?, 'pending', NOW())
    ");
    $insertStmt->execute([$tournamentId, $teamId]);

    // Success redirect
    header("Location: details.php?id={$tournamentId}&registered=1");
    exit;

} catch (PDOException $e) {
    die("Database Error during registration: " . $e->getMessage());
}
