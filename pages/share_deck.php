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

// Fetch deck details
$stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ? AND user_id = ?");
$stmt->execute([$deck_id, $user_id]);
$deck = $stmt->fetch();

if (!$deck) {
    header("Location: view_decks.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shared_with = $_POST['shared_with'];
    $permission = $_POST['permission'];

    // Check if the user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$shared_with]);
    $shared_user = $stmt->fetch();

    if ($shared_user) {
        // Share the deck
        $stmt = $pdo->prepare("INSERT INTO shared_decks (deck_id, shared_by, shared_with, permission) VALUES (?, ?, ?, ?)");
        $stmt->execute([$deck_id, $user_id, $shared_user['id'], $permission]);
        $success = "Deck shared successfully!";
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Deck</title>
    <link href="../src/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <h1 class="text-2xl font-bold">Share Deck</h1>
    </header>
    <main class="p-4">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-semibold mb-4">Share <?php echo htmlspecialchars($deck['title']); ?></h2>
                <?php if (isset($success)): ?>
                    <p class="text-green-500"><?php echo $success; ?></p>
                <?php elseif (isset($error)): ?>
                    <p class="text-red-500"><?php echo $error; ?></p>
                <?php endif; ?>
                <form method="POST" action="share_deck.php?deck_id=<?php echo $deck_id; ?>">
                    <div class="mb-4">
                        <label for="shared_with" class="block text-sm font-medium text-gray-700">Share With (Username)</label>
                        <input type="text" name="shared_with" id="shared_with" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div class="mb-4">
                        <label for="permission" class="block text-sm font-medium text-gray-700">Permission</label>
                        <select name="permission" id="permission" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
                            <option value="view">View Only</option>
                            <option value="edit">Edit</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Share</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>