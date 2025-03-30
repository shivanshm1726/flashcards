<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? 0;

if ($session_id <= 0) {
    header("Location: custom_study_sessions.php?error=no_session");
    exit();
}

// Fetch the custom study session
$stmt = $pdo->prepare("SELECT name, time_limit FROM custom_study_sessions WHERE id = ? AND user_id = ?");
$stmt->execute([$session_id, $user_id]);
$session = $stmt->fetch();

if (!$session) {
    header("Location: custom_study_sessions.php?error=invalid_session");
    exit();
}

// Fetch all decks (including shared decks)
$stmt = $pdo->prepare("
    SELECT d.*, u.username AS owner_name, 
           COUNT(f.id) AS total_flashcards
    FROM decks d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN flashcards f ON d.id = f.deck_id
    LEFT JOIN shared_decks sd ON d.id = sd.deck_id AND sd.shared_with = ?
    WHERE (d.user_id = ? OR sd.shared_with = ?)
    GROUP BY d.id
");
$stmt->execute([$user_id, $user_id, $user_id]);
$decks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Decks for Custom Study Session</title>
    <link href="../src/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">
    <header class="bg-blue-600 text-white p-4 shadow-lg w-full">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Select Decks for: <?php echo htmlspecialchars($session['name']); ?></h1>
            <a href="custom_study_sessions.php" class="text-sm hover:underline">Back</a>
        </div>
    </header>
    <main class="flex-1 p-4">
        <div class="max-w-4xl mx-auto">
            <?php if (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] === 'no_decks_selected'): ?>
                    <p class="text-red-500 mb-4">Please select at least one deck to start the session.</p>
                <?php elseif ($_GET['error'] === 'database_error'): ?>
                    <p class="text-red-500 mb-4">An error occurred while starting the session. Please try again later.</p>
                <?php endif; ?>
            <?php endif; ?>
            <form id="deck-selection-form" action="start_custom_study.php" method="POST">
                <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                <input type="hidden" name="start_session" value="1">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <?php if (empty($decks)): ?>
                        <p class="text-gray-600">No decks found. <a href="create_deck.php" class="text-blue-500 hover:underline">Create a deck</a> to get started.</p>
                    <?php else: ?>
                        <?php foreach ($decks as $deck): ?>
                            <div class="deck-card bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-200 cursor-pointer" data-deck-id="<?php echo $deck['id']; ?>">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($deck['title']); ?></h3>
                                <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($deck['description'] ?? 'No description'); ?></p>
                                <p class="text-sm text-gray-500 mb-4">Flashcards: <?php echo $deck['total_flashcards']; ?></p>
                                <p class="text-sm text-gray-500 mb-4">Owner: <?php echo htmlspecialchars($deck['owner_name']); ?></p>
                                <button type="button" class="select-deck-btn w-full py-2 px-4 rounded-md text-white bg-blue-500 hover:bg-blue-600">Select</button>
                                <input type="hidden" name="decks[]" value="<?php echo $deck['id']; ?>" disabled>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="text-center">
                    <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600 disabled:bg-gray-400" id="start-session-btn" disabled>
                        Start Session
                    </button>
                </div>
            </form>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deckCards = document.querySelectorAll('.deck-card');
            const startSessionBtn = document.getElementById('start-session-btn');
            let selectedDecks = new Set();

            deckCards.forEach(card => {
                const selectBtn = card.querySelector('.select-deck-btn');
                const input = card.querySelector('input[name="decks[]"]');

                card.addEventListener('click', (e) => {
                    if (e.target !== selectBtn) {
                        selectBtn.click();
                    }
                });

                selectBtn.addEventListener('click', () => {
                    const deckId = card.getAttribute('data-deck-id');
                    if (selectedDecks.has(deckId)) {
                        selectedDecks.delete(deckId);
                        card.classList.remove('border-blue-500', 'border-2');
                        selectBtn.textContent = 'Select';
                        selectBtn.classList.remove('bg-red-500', 'hover:bg-red-600');
                        selectBtn.classList.add('bg-blue-500', 'hover:bg-blue-600');
                        input.disabled = true;
                    } else {
                        selectedDecks.add(deckId);
                        card.classList.add('border-blue-500', 'border-2');
                        selectBtn.textContent = 'Deselect';
                        selectBtn.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                        selectBtn.classList.add('bg-red-500', 'hover:bg-red-600');
                        input.disabled = false;
                    }

                    // Enable/disable Start Session button
                    startSessionBtn.disabled = selectedDecks.size === 0;
                });
            });
        });
    </script>
</body>
</html>