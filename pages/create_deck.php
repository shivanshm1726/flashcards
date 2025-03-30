<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch categories from the database
$stmt = $pdo->prepare("SELECT id, name FROM categories");
$stmt->execute();
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];

    // Insert the new deck
    $stmt = $pdo->prepare("INSERT INTO decks (user_id, title, description, category_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $description, $category_id]);

    // Get the ID of the newly created deck
    $deck_id = $pdo->lastInsertId();

    // Redirect to add_flashcards.php with the deck_id
    header("Location: add_flashcards.php?deck_id=" . $deck_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Deck</title>
    <link href="../src/output.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-6 shadow-lg">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Create Deck</h1>
            <a href="index.php" class="text-sm text-white hover:underline">Back to Dashboard</a>
        </div>
    </header>
    <main class="p-6">
        <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md border border-gray-200">
            <form method="POST" action="create_deck.php">
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700">Deck Title</label>
                    <input type="text" name="title" id="title" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700">Deck Description</label>
                    <textarea name="description" id="description" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="mb-4">
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">No Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Create Deck</button>
            </form>
        </div>
    </main>
</body>

</html>