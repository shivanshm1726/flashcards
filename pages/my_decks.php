<?php
// my_decks.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch ONLY the user's OWN decks
$stmt = $pdo->prepare("
    SELECT d.*, COUNT(f.id) AS total_flashcards 
    FROM decks d
    LEFT JOIN flashcards f ON d.id = f.deck_id
    WHERE d.user_id = ?
    GROUP BY d.id
");
$stmt->execute([$user_id]);
$decks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Decks</title>
    <link href="../src/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-6 shadow-lg">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">My Decks</h1>
            <div class="space-x-4">
                <a href="view_decks.php" class="text-sm text-white hover:underline">View All Decks</a>
                <a href="index.php" class="text-sm text-white hover:underline">Dashboard</a>
            </div>
        </div>
    </header>
    <main class="p-6">
        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($decks)): ?>
                    <p class="text-gray-600">You have no decks. <a href="create_deck.php" class="text-blue-500 hover:underline">Create one</a>.</p>
                <?php else: ?>
                    <?php foreach ($decks as $deck): ?>
                        <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($deck['title']); ?></h3>
                            <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($deck['description']); ?></p>
                            <p class="text-sm text-gray-500 mb-4">Flashcards: <?php echo $deck['total_flashcards']; ?></p>
                            <div class="flex space-x-3 mt-4">
                                <a href="study.php?deck_id=<?php echo $deck['id']; ?>" class="btn-primary">Study</a>
                                <a href="edit_deck.php?deck_id=<?php echo $deck['id']; ?>" class="btn-secondary">Edit</a>
                                <a href="delete_deck.php?deck_id=<?php echo $deck['id']; ?>" class="btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>