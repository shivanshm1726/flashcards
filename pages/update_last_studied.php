<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit();
}

include '../includes/db.php';
$user_id = $_SESSION['user_id'];
$deck_id = $_POST['deck_id'] ?? 0;

if ($deck_id > 0) {
    $stmt = $pdo->prepare("UPDATE decks SET last_studied = CURDATE() WHERE id = ? AND user_id = ?");
    $stmt->execute([$deck_id, $user_id]);
}
?>