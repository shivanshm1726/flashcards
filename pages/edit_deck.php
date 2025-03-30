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

// Fetch deck details and verify ownership
$stmt = $pdo->prepare("SELECT id, title, description FROM decks WHERE id = ? AND user_id = ?");
$stmt->execute([$deck_id, $user_id]);
$deck = $stmt->fetch();

if (!$deck) {
    header("Location: view_decks.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("UPDATE decks SET title = ?, description = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$title, $description, $deck_id, $user_id]);

    header("Location: view_decks.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Deck</title>
    <link href="../src/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-6 shadow-lg">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Edit Deck</h1>
            <a href="view_decks.php" class="text-sm text-white hover:underline">Back to Decks</a>
        </div>
    </header>
    <main class="p-6">
        <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md border border-gray-200">
            <form method="POST" action="edit_deck.php?deck_id=<?php echo $deck_id; ?>">
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700">Deck Title</label>
                    <input type="text" name="title" id="title" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($deck['title']); ?>" required>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700">Deck Description</label>
                    <textarea name="description" id="description" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($deck['description']); ?></textarea>
                </div>
                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
        </div>
    </main>
</body>
</html>