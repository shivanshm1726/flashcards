<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$session_id = $_POST['session_id'] ?? 0;
$deck_id = $_POST['deck_id'] ?? 0;
$index = $_POST['index'] ?? 0;

if ($session_id > 0 && $deck_id > 0) {
    $_SESSION['current_index'][$session_id][$deck_id] = (int)$index;
}

echo json_encode(['status' => 'success']);
?>