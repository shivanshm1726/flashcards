<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_decks = (int) ($_POST['target_decks'] ?? 0);
    $period = $_POST['period'] ?? 'daily';

    // Ensure target_decks is at least 1
    if ($target_decks < 1 || !in_array($period, ['daily', 'weekly'])) {
        header("Location: index.php?error=invalid_input");
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO study_goals (user_id, target_decks, period) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $target_decks, $period]);
    header("Location: index.php?goal_set=success");
    exit();
} else {
    header("Location: index.php?error=invalid_method");
    exit();
}
?>