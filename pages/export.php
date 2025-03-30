<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'progress';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="export_' . $type . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

if ($type === 'progress') {
    // Export study progress
    fputcsv($output, ['Deck', 'Total Flashcards', 'Mastered Flashcards', 'Mastery Percentage']);
    $stmt = $pdo->prepare("
        SELECT d.title, 
               COUNT(f.id) AS total_flashcards,
               COUNT(s.id) AS mastered_flashcards,
               ROUND(COUNT(s.id) / COUNT(f.id) * 100, 2) AS mastery_percentage
        FROM decks d
        LEFT JOIN flashcards f ON d.id = f.deck_id
        LEFT JOIN study_sessions s ON f.id = s.card_id AND s.status = 'mastered'
        WHERE d.user_id = ?
        GROUP BY d.id
    ");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} elseif ($type === 'sessions') {
    // Export session history
    fputcsv($output, ['Session Name', 'Time Limit (minutes)', 'Created At']);
    $stmt = $pdo->prepare("SELECT name, time_limit, created_at FROM custom_study_sessions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit();
?>