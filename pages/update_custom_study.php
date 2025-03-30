<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
$user_id = $_SESSION['user_id'];
$session_id = $_POST['session_id'] ?? 0;
$deck_id = $_POST['deck_id'] ?? 0;
$action = $_POST['action'] ?? '';

if ($session_id <= 0) {
    header("Location: index.php");
    exit();
}

if ($action === 'end_session') {
    // Mark the session as completed
    $stmt = $pdo->prepare("UPDATE custom_study_sessions SET status = 'completed' WHERE id = ? AND user_id = ?");
    $stmt->execute([$session_id, $user_id]);
    // Clear timer data
    unset($_SESSION['timer'][$session_id]);
    header("Location: index.php");
    exit();
}

if ($deck_id <= 0 || $action !== 'complete') {
    header("Location: study_custom_session.php?session_id=$session_id");
    exit();
}

// Update the deck status in custom_study_session_decks
$stmt = $pdo->prepare("
    UPDATE custom_study_session_decks 
    SET status = 'completed'
    WHERE session_id = ? AND deck_id = ?
");
$stmt->execute([$session_id, $deck_id]);

// Update last_studied for the deck
$stmt = $pdo->prepare("UPDATE decks SET last_studied = CURDATE() WHERE id = ?");
$stmt->execute([$deck_id]);

// Clear the current index for this deck
unset($_SESSION['current_index'][$session_id][$deck_id]);

header("Location: study_custom_session.php?session_id=$session_id");
exit();
?>