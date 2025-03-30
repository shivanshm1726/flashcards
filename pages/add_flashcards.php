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

// Check if the deck belongs to the user
$stmt = $pdo->prepare("SELECT id FROM decks WHERE id = ? AND user_id = ?");
$stmt->execute([$deck_id, $user_id]);
$deck = $stmt->fetch();

if (!$deck) {
    header("Location: view_decks.php");
    exit();
}

// Add flashcard logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = $_POST['question'];
    $answer = $_POST['answer'];

    // Insert the flashcard
    $stmt = $pdo->prepare("INSERT INTO flashcards (deck_id, question, answer) VALUES (?, ?, ?)");
    $stmt->execute([$deck_id, $question, $answer]);

    // Redirect back to add_flashcards.php to add more flashcards
    header("Location: add_flashcards.php?deck_id=" . $deck_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Flashcards</title>
    <link href="../src/output.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-6 shadow-lg">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Add Flashcards</h1>
            <a href="view_decks.php" class="text-sm text-white hover:underline">Back to Decks</a>
        </div>
    </header>
    <main class="p-6">
        <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md border border-gray-200">
            <form method="POST" action="add_flashcards.php?deck_id=<?php echo $deck_id; ?>">
                <div class="mb-4">
                    <label for="question" class="block text-sm font-medium text-gray-700">Question</label>
                    <input type="text" name="question" id="question" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="answer" class="block text-sm font-medium text-gray-700">Answer</label>
                    <textarea name="answer" id="answer" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required></textarea>
                </div>
                <button type="submit" class="btn-primary">Add Flashcard</button>
            </form>
        </div>
    </main>
</body>

</html>