<?php
session_start();
include '../includes/db.php';

$user_id = $_POST['user_id'] ?? null;
$deck_id = $_POST['deck_id'] ?? null;
$card_id = $_POST['card_id'] ?? null;

if (!$user_id || !$deck_id || !$card_id || $user_id != $_SESSION['user_id']) {
    http_response_code(400);
    exit();
}

$stmt = $pdo->prepare("
    INSERT INTO study_sessions (user_id, deck_id, card_id, status, studied_at, next_review, interval_days)
    VALUES (?, ?, ?, 'reviewed', NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 1)
    ON DUPLICATE KEY UPDATE studied_at = NOW(), status = 'reviewed'
");
$stmt->execute([$user_id, $deck_id, $card_id]);

$stmt = $pdo->prepare("INSERT INTO user_points (user_id, points) VALUES (?, 5)");
$stmt->execute([$user_id]);
?>