<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];
$search_query = $_GET['q'] ?? '';
$results = [];

if (!empty($search_query)) {
    $stmt = $pdo->prepare("
        SELECT f.id AS flashcard_id, f.question, f.answer, d.title AS deck_title, d.id AS deck_id
        FROM flashcards f
        JOIN decks d ON f.deck_id = d.id
        LEFT JOIN shared_decks sd ON d.id = sd.deck_id
        WHERE (d.user_id = ? OR sd.shared_with = ?)
          AND (f.question LIKE ? OR f.answer LIKE ? OR d.title LIKE ?)
    ");
    $search_term = "%$search_query%";
    $stmt->execute([$user_id, $user_id, $search_term, $search_term, $search_term]);
    $results = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search</title>
    <link href="../src/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <h1 class="text-2xl font-bold">Search Results</h1>
    </header>
    <main class="p-4">
        <div class="max-w-4xl mx-auto">
            <?php if (empty($results)): ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <p class="text-gray-600">No results found for "<?php echo htmlspecialchars($search_query); ?>".</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($results as $result): ?>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($result['question']); ?></h3>
                            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($result['answer']); ?></p>
                            <p class="text-sm text-gray-500 mt-2">
                                Deck: <a href="study.php?deck_id=<?php echo $result['deck_id']; ?>" class="text-blue-500">
                                    <?php echo htmlspecialchars($result['deck_title']); ?>
                                </a>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>