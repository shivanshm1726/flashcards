<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];
$deck_id = $_GET['deck_id'] ?? null;

if (!$deck_id) {
    header("Location: view_decks.php");
    exit();
}

// Verify ownership before deleting
$stmt = $pdo->prepare("SELECT id FROM decks WHERE id = ? AND user_id = ?");
$stmt->execute([$deck_id, $user_id]);
$deck = $stmt->fetch();

if (!$deck) {
    header("Location: view_decks.php");
    exit();
}

// Delete the deck and its flashcards
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("DELETE FROM flashcards WHERE deck_id = ?");
    $stmt->execute([$deck_id]);

    $stmt = $pdo->prepare("DELETE FROM decks WHERE id = ?");
    $stmt->execute([$deck_id]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("An error occurred while deleting the deck.");
}

header("Location: view_decks.php");
exit();
?>