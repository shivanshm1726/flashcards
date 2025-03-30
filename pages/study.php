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
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT title, is_mastered FROM decks WHERE id = ? AND user_id = ?");
$stmt->execute([$deck_id, $user_id]);
$deck = $stmt->fetch();

if (!$deck) {
    header("Location: index.php");
    exit();
}

$deck['is_mastered'] = (bool) $deck['is_mastered'];

$stmt = $pdo->prepare("SELECT id, question, answer FROM flashcards WHERE deck_id = ?");
$stmt->execute([$deck_id]);
$flashcards = $stmt->fetchAll();

if (empty($flashcards)) {
    header("Location: index.php");
    exit();
}

// Function to check achievements
function checkAchievements($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM decks WHERE user_id = ? AND is_mastered = 1");
    $stmt->execute([$user_id]);
    $mastered_decks = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM study_sessions WHERE user_id = ? AND status IN ('reviewed', 'mastered')");
    $stmt->execute([$user_id]);
    $studied_flashcards = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT id, name FROM achievements WHERE id NOT IN (SELECT achievement_id FROM user_achievements WHERE user_id = ?)");
    $stmt->execute([$user_id]);
    $available_achievements = $stmt->fetchAll();

    foreach ($available_achievements as $achievement) {
        if ($achievement['name'] === 'Deck Master' && $mastered_decks >= 5) {
            $stmt = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $achievement['id']]);
        } elseif ($achievement['name'] === 'Study Enthusiast' && $studied_flashcards >= 100) {
            $stmt = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $achievement['id']]);
        }
    }
}

// Handle mastery toggle
$debug_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_mastery'])) {
    $new_status = $deck['is_mastered'] ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE decks SET is_mastered = ?, last_studied_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$new_status, $deck_id, $user_id]);

    if ($new_status) {
        $stmt = $pdo->prepare("
            INSERT INTO study_sessions (user_id, deck_id, card_id, status, studied_at, next_review, interval_days)
            SELECT ?, ?, f.id, 'mastered', NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 1
            FROM flashcards f WHERE f.deck_id = ?
            ON DUPLICATE KEY UPDATE status = 'mastered', studied_at = NOW(), next_review = DATE_ADD(NOW(), INTERVAL 1 DAY)
        ");
        $stmt->execute([$user_id, $deck_id, $deck_id]);

        $stmt = $pdo->prepare("INSERT INTO user_points (user_id, points) VALUES (?, 50)");
        $stmt->execute([$user_id]);

        checkAchievements($pdo, $user_id);
    }

    $stmt = $pdo->prepare("SELECT is_mastered FROM decks WHERE id = ? AND user_id = ?");
    $stmt->execute([$deck_id, $user_id]);
    $updated_deck = $stmt->fetch();
    $debug_message = "Deck mastery updated to: " . ($updated_deck['is_mastered'] ? 'Mastered' : 'Not Mastered');

    header("Location: study.php?deck_id=$deck_id&debug=" . urlencode($debug_message));
    exit();
}

// Update last_studied_at
$stmt = $pdo->prepare("UPDATE decks SET last_studied_at = NOW() WHERE id = ? AND user_id = ?");
$stmt->execute([$deck_id, $user_id]);

$debug_message = $_GET['debug'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study - <?php echo htmlspecialchars($deck['title']); ?></title>
    <link href="../src/output.css" rel="stylesheet">
    <style>
        .flip-card { perspective: 1000px; }
        .flip-card-inner { position: relative; width: 100%; height: 100%; transition: transform 0.6s; transform-style: preserve-3d; }
        .flip-card-front, .flip-card-back { position: absolute; width: 100%; height: 100%; backface-visibility: hidden; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; padding: 1.5rem; background-color: white; }
        .flip-card-back { transform: rotateY(180deg); }
        .flipped .flip-card-inner { transform: rotateY(180deg); }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-6 shadow-lg">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Study - <?php echo htmlspecialchars($deck['title']); ?></h1>
            <a href="index.php" class="text-sm text-white hover:underline">Back to Dashboard</a>
        </div>
    </header>
    <main class="p-6">
        <div class="max-w-md mx-auto">
            <?php if ($debug_message): ?>
                <p class="text-green-500 mb-4"><?php echo htmlspecialchars($debug_message); ?></p>
            <?php endif; ?>
            <form method="POST" action="study.php?deck_id=<?php echo $deck_id; ?>" id="masteryForm">
                <div class="flip-card w-full h-64 bg-white rounded-lg shadow-md cursor-pointer" onclick="flipCard()" id="flipCard">
                    <div class="flip-card-inner">
                        <div id="front" class="flip-card-front">
                            <p class="text-xl font-semibold text-center"><?php echo htmlspecialchars($flashcards[0]['question']); ?></p>
                        </div>
                        <div id="back" class="flip-card-back">
                            <p class="text-xl font-semibold text-center"><?php echo htmlspecialchars($flashcards[0]['answer']); ?></p>
                        </div>
                    </div>
                </div>
                <div id="masteryButtonContainer" class="mt-4"></div>
            </form>
            <div class="flex justify-between mt-6">
                <button onclick="prevCard()" class="btn-primary" id="prevBtn">Previous</button>
                <button onclick="nextCard()" class="btn-primary" id="nextBtn">Next</button>
            </div>
            <input type="hidden" id="viewedCards" value="[]">
            <p id="viewProgress" class="text-sm text-gray-500 mt-2">Viewed: 0/<?php echo count($flashcards); ?> cards</p>
        </div>
    </main>
    <script>
        let currentCardIndex = 0;
        const flashcards = <?php echo json_encode($flashcards); ?>;
        const totalCards = flashcards.length;
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const isMastered = <?php echo $deck['is_mastered'] ? 'true' : 'false'; ?>;
        const deckId = <?php echo $deck_id; ?>;
        const userId = <?php echo $user_id; ?>;

        function flipCard() {
            const flipCard = document.getElementById('flipCard');
            flipCard.classList.toggle('flipped');
        }

        function updateStudySession(cardId) {
            fetch('update_study_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&deck_id=${deckId}&card_id=${cardId}`
            });
        }

        function showCard(index) {
            const front = document.getElementById('front');
            const back = document.getElementById('back');
            const masteryButtonContainer = document.getElementById('masteryButtonContainer');
            front.innerHTML = `<p class="text-xl font-semibold text-center">${flashcards[index].question}</p>`;
            back.innerHTML = `<p class="text-xl font-semibold text-center">${flashcards[index].answer}</p>`;
            const flipCard = document.getElementById('flipCard');
            flipCard.classList.remove('flipped');

            const viewedCards = JSON.parse(document.getElementById('viewedCards').value);
            if (!viewedCards.includes(index)) {
                viewedCards.push(index);
                document.getElementById('viewedCards').value = JSON.stringify(viewedCards);
                updateStudySession(flashcards[index].id);
            }
            updateProgress();

            if (index === totalCards - 1) {
                const buttonDisabled = viewedCards.length < totalCards ? 'disabled' : '';
                masteryButtonContainer.innerHTML = `
                    <button type="submit" name="toggle_mastery" id="masteryBtn"
                        class="w-full py-2 px-4 rounded-md text-white ${isMastered ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'}"
                        ${buttonDisabled}>
                        ${isMastered ? 'Mark as Not Mastered' : 'Mark Deck as Mastered'}
                    </button>
                `;
            } else {
                masteryButtonContainer.innerHTML = '';
            }

            prevBtn.disabled = (index === 0);
            nextBtn.disabled = (index === totalCards - 1);
        }

        function updateProgress() {
            const viewedCards = JSON.parse(document.getElementById('viewedCards').value);
            document.getElementById('viewProgress').textContent = `Viewed: ${viewedCards.length}/${totalCards} cards`;
            const masteryBtn = document.getElementById('masteryBtn');
            if (masteryBtn && viewedCards.length === totalCards) {
                masteryBtn.disabled = false;
            }
        }

        function nextCard() {
            if (currentCardIndex < totalCards - 1) {
                currentCardIndex++;
                showCard(currentCardIndex);
            }
        }

        function prevCard() {
            if (currentCardIndex > 0) {
                currentCardIndex--;
                showCard(currentCardIndex);
            }
        }

        showCard(0);
    </script>
</body>
</html>