<?php
session_start();
include 'includes/db.php';

if (isset($_GET['user'])) {
    $stmt = $pdo->prepare("UPDATE users SET receive_emails = 0 WHERE id = ?");
    $stmt->execute([$_GET['user']]);
    echo "You have been unsubscribed from email notifications.";
}