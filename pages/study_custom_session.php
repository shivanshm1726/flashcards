<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
$user_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? 0;
$deck_id = $_GET['deck_id'] ?? 0;

if ($session_id <= 0) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT name, time_limit, status FROM custom_study_sessions WHERE id = ? AND user_id = ?");
$stmt->execute([$session_id, $user_id]);
$session = $stmt->fetch();
if (!$session) {
    header("Location: index.php");
    exit();
}

// Fetch selected decks and their statuses
$stmt = $pdo->prepare("
    SELECT csd.deck_id, csd.status, d.title
    FROM custom_study_session_decks csd
    JOIN decks d ON csd.deck_id = d.id
    WHERE csd.session_id = ?
");
$stmt->execute([$session_id]);
$decks = $stmt->fetchAll();

// Calculate remaining time
$timer_data = $_SESSION['timer'][$session_id] ?? null;
if ($timer_data) {
    $time_limit = $timer_data['time_limit'];
    $start_time = $timer_data['start_time'];
    $elapsed_time = time() - $start_time;
    $time_left = max(0, $time_limit - $elapsed_time);
} else {
    $time_left = $session['time_limit'] * 60; // Fallback if timer data is not set
}

// If a deck is selected, fetch its flashcards
$flashcards = [];
$current_flashcard = null;
$current_index = isset($_SESSION['current_index'][$session_id][$deck_id]) ? $_SESSION['current_index'][$session_id][$deck_id] : 0;

if ($deck_id > 0) {
    $stmt = $pdo->prepare("
        SELECT f.id, f.question, f.answer
        FROM custom_study_session_flashcards csf
        JOIN flashcards f ON csf.flashcard_id = f.id
        WHERE csf.session_id = ? AND f.deck_id = ?
    ");
    $stmt->execute([$session_id, $deck_id]);
    $flashcards = $stmt->fetchAll();

    if (!empty($flashcards) && $current_index < count($flashcards)) {
        $current_flashcard = $flashcards[$current_index];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Custom Session</title>
    <link href="../src/output.css" rel="stylesheet">
    <style>
        .flip-card {
            perspective: 1000px;
        }

        .flip-card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .flip-card-front,
        .flip-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            padding: 1.5rem;
            background-color: white;
        }

        .flip-card-back {
            transform: rotateY(180deg);
        }

        .flipped .flip-card-inner {
            transform: rotateY(180deg);
        }

        .deck-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .deck-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
    <script>
        let timeLeft = <?php echo $time_left; ?>;
        const totalCards = <?php echo count($flashcards); ?>;
        let currentIndex = <?php echo $current_index; ?>;
        const flashcards = <?php echo json_encode($flashcards); ?>;

        function startTimer() {
            let timer = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    alert("Time's up!");
                    // Clear timer data via an AJAX call
                    fetch('update_custom_study.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `session_id=<?php echo $session_id; ?>&action=end_session`
                    }).then(() => {
                        window.location.href = "index.php";
                    });
                } else {
                    timeLeft--;
                    let minutes = Math.floor(timeLeft / 60);
                    let seconds = timeLeft % 60;
                    document.getElementById("timer").innerText = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                }
            }, 1000);
        }

        function flipCard() {
            const flipCard = document.getElementById('flipCard');
            flipCard.classList.toggle('flipped');
        }

        function showCard(index) {
            const front = document.getElementById('front');
            const back = document.getElementById('back');
            const progress = document.getElementById('progress');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            front.innerHTML = `<p class="text-xl font-semibold text-center">${flashcards[index].question}</p>`;
            back.innerHTML = `<p class="text-xl font-semibold text-center">${flashcards[index].answer}</p>`;
            progress.textContent = `Card ${index + 1} of ${totalCards}`;

            const flipCard = document.getElementById('flipCard');
            flipCard.classList.remove('flipped');

            prevBtn.disabled = (index === 0);
            nextBtn.disabled = (index === totalCards - 1);
        }

        function nextCard() {
            if (currentIndex < totalCards - 1) {
                currentIndex++;
                fetch('update_index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `session_id=<?php echo $session_id; ?>&deck_id=<?php echo $deck_id; ?>&index=${currentIndex}`
                });
                showCard(currentIndex);
            }
        }

        function prevCard() {
            if (currentIndex > 0) {
                currentIndex--;
                fetch('update_index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `session_id=<?php echo $session_id; ?>&deck_id=<?php echo $deck_id; ?>&index=${currentIndex}`
                });
                showCard(currentIndex);
            }
        }

        window.onload = () => {
            startTimer();
            if (totalCards > 0) {
                showCard(currentIndex);
            }
        };
    </script>
</head>

<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($session['name']); ?></h1>
            <div class="space-x-4">
                <form action="update_custom_study.php" method="POST" class="inline">
                    <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                    <input type="hidden" name="action" value="end_session">
                    <button type="submit" class="text-sm hover:underline">End Session</button>
                </form>
                <a href="index.php" class="text-sm hover:underline">Back to Dashboard</a>
            </div>
        </div>
    </header>
    <main class="p-4">
        <div class="max-w-4xl mx-auto">
            <p class="text-gray-600 mb-4">Time Remaining: <span id="timer"><?php echo floor($time_left / 60); ?>:<?php echo str_pad($time_left % 60, 2, '0', STR_PAD_LEFT); ?></span></p>
            <?php if ($deck_id > 0 && $current_flashcard): ?>
                <!-- Studying a Deck -->
                <div class="flip-card w-full h-64 bg-white rounded-lg shadow-md cursor-pointer" onclick="flipCard()" id="flipCard">
                    <div class="flip-card-inner">
                        <div class="flip-card-front" id="front">
                            <p class="text-xl font-semibold text-center"><?php echo htmlspecialchars($current_flashcard['question']); ?></p>
                        </div>
                        <div class="flip-card-back" id="back">
                            <p class="text-xl font-semibold text-center"><?php echo htmlspecialchars($current_flashcard['answer']); ?></p>
                        </div>
                    </div>
                </div>
                <p id="progress" class="text-sm text-gray-500 mt-2 text-center">Card <?php echo $current_index + 1; ?> of <?php echo count($flashcards); ?></p>
                <div class="flex justify-between mt-4">
                    <button onclick="prevCard()" class="btn-primary" id="prevBtn">Previous</button>
                    <button onclick="nextCard()" class="btn-primary" id="nextBtn">Next</button>
                </div>
                <div class="mt-4 flex justify-center space-x-4">
                    <a href="study_custom_session.php?session_id=<?php echo $session_id; ?>" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">Close Deck</a>
                    <?php if ($current_index === count($flashcards) - 1): ?>
                        <form action="update_custom_study.php" method="POST" class="inline">
                            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                            <input type="hidden" name="deck_id" value="<?php echo $deck_id; ?>">
                            <button type="submit" name="action" value="complete" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Finish Deck</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Deck Selection Screen -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4">Select a Deck to Study</h2>
                    <?php if (empty($decks)): ?>
                        <p class="text-gray-600">No decks available for this session.</p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($decks as $deck): ?>
                                <div class="deck-card bg-white p-4 rounded-lg shadow-md border <?php echo $deck['status'] === 'completed' ? 'border-green-500' : 'border-gray-200'; ?>">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($deck['title']); ?></h3>
                                    <p class="text-sm text-gray-500 mb-2">Status:
                                        <span class="<?php echo $deck['status'] === 'completed' ? 'text-green-500' : 'text-yellow-500'; ?>">
                                            <?php echo $deck['status'] === 'completed' ? 'Completed' : 'Pending'; ?>
                                        </span>
                                    </p>
                                    <?php if ($deck['status'] !== 'completed'): ?>
                                        <a href="study_custom_session.php?session_id=<?php echo $session_id; ?>&deck_id=<?php echo $deck['deck_id']; ?>" class="btn-primary mt-2 inline-block">Study Deck</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php
                        // Check if all decks are completed
                        $all_completed = true;
                        foreach ($decks as $deck) {
                            if ($deck['status'] !== 'completed') {
                                $all_completed = false;
                                break;
                            }
                        }
                        if ($all_completed) {
                            $stmt = $pdo->prepare("UPDATE custom_study_sessions SET status = 'completed' WHERE id = ?");
                            $stmt->execute([$session_id]);
                        }
                        ?>
                        <?php if ($all_completed): ?>
                            <div class="mt-6 text-center">
                                <p class="text-green-500 font-semibold mb-4">All decks in this session have been completed!</p>
                                <a href="index.php" class="text-blue-500 hover:underline">Back to Dashboard</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>