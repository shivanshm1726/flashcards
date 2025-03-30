<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch user details
try {
    $stmt = $pdo->prepare("SELECT email, language FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        throw new Exception("User not found.");
    }
} catch (Exception $e) {
    error_log("Error fetching user: " . $e->getMessage());
    header("Location: index.php?error=database_error");
    exit();
}

// Fetch user stats (removed total_flashcards, mastered_flashcards, and level)
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT d.id) AS total_decks,
            DATEDIFF(MAX(ss.studied_at), MIN(ss.studied_at)) AS study_streak,
            up.points
        FROM users u
        LEFT JOIN decks d ON d.user_id = u.id
        LEFT JOIN study_sessions ss ON ss.user_id = u.id
        LEFT JOIN (
            SELECT points FROM user_points WHERE user_id = ? ORDER BY id DESC LIMIT 1
        ) up ON 1=1
        WHERE u.id = ?
        GROUP BY u.id, up.points
    ");
    $stmt->execute([$user_id, $user_id]);
    $stats = $stmt->fetch();
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
    $stats = ['total_decks' => 0, 'study_streak' => 0, 'points' => 0];
}

// Fetch user achievements
try {
    $stmt = $pdo->prepare("
        SELECT a.name, a.description, a.badge_icon 
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $achievements = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching achievements: " . $e->getMessage());
    $achievements = [];
}

// Fetch study activity for the last 30 days
try {
    $stmt = $pdo->prepare("
        SELECT DATE(studied_at) AS date, COUNT(*) AS count 
        FROM study_sessions 
        WHERE user_id = ? 
          AND studied_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(studied_at)
    ");
    $stmt->execute([$user_id]);
    $study_activity = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    error_log("Error fetching study activity: " . $e->getMessage());
    $study_activity = [];
}

// Fetch study progress for the last 7 days
try {
    $stmt = $pdo->prepare("
        SELECT DATE(studied_at) AS date, COUNT(*) AS count 
        FROM study_sessions 
        WHERE user_id = ? 
          AND studied_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(studied_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$user_id]);
    $study_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    error_log("Error fetching study progress: " . $e->getMessage());
    $study_data = [];
}

// Prepare labels and data points for the graph
$labels = [];
$data_points = [];
$current_date = new DateTime('-6 days');
for ($i = 0; $i < 7; $i++) {
    $date_str = $current_date->format('Y-m-d');
    $labels[] = $current_date->format('D, M j');
    $data_points[] = $study_data[$date_str] ?? 0;
    $current_date->modify('+1 day');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_email'])) {
        $new_email = $_POST['email'];
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $email_error = "Invalid email format.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$new_email, $user_id]);
                header("Location: profile.php");
                exit();
            } catch (Exception $e) {
                error_log("Error updating email: " . $e->getMessage());
                $email_error = "Failed to update email.";
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_password = $stmt->fetchColumn();

            if (!password_verify($current_password, $user_password)) {
                $password_error = "Current password is incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $password_error = "New passwords do not match.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                header("Location: profile.php");
                exit();
            }
        } catch (Exception $e) {
            error_log("Error updating password: " . $e->getMessage());
            $password_error = "Failed to update password.";
        }
    } elseif (isset($_POST['update_language'])) {
        $language = $_POST['language'];
        if (!in_array($language, ['en', 'es', 'fr'])) {
            $language_error = "Invalid language selected.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
                $stmt->execute([$language, $user_id]);
                $_SESSION['language'] = $language;
                header("Location: profile.php");
                exit();
            } catch (Exception $e) {
                error_log("Error updating language: " . $e->getMessage());
                $language_error = "Failed to update language.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="../src/output.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-6 shadow-lg">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Profile</h1>
            <a href="index.php" class="text-sm text-white hover:underline">Back to Dashboard</a>
        </div>
    </header>
    <main class="p-6">
        <div class="max-w-4xl mx-auto">
            <!-- Quick Stats -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-semibold mb-4">Quick Stats</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Total Decks</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['total_decks'] ?? 0; ?></p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Study Streak</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['study_streak'] ?? 0; ?> days</p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Points</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['points'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <!-- Study Activity Calendar -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-semibold mb-4">Study Activity</h2>
                <div class="grid grid-cols-7 gap-1">
                    <?php
                    $today = new DateTime();
                    for ($i = 29; $i >= 0; $i--) {
                        $date = clone $today;
                        $date->modify("-$i days");
                        $date_str = $date->format('Y-m-d');
                        $activity = $study_activity[$date_str] ?? 0;

                        $color_class = match (true) {
                            $activity >= 5 => 'bg-green-600',
                            $activity >= 3 => 'bg-green-400',
                            $activity >= 1 => 'bg-green-200',
                            default => 'bg-gray-100'
                        };
                    ?>
                        <div class="h-8 <?= $color_class ?> rounded-sm"
                            title="<?= $date->format('M j, Y') ?>: <?= $activity ?> cards studied"></div>
                    <?php } ?>
                </div>
                <p class="text-sm text-gray-500 mt-2">Study activity over the last 30 days</p>
            </div>

            <!-- Study Progress Graph -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-semibold mb-4">Study Progress</h2>
                <div class="w-full h-64">
                    <canvas id="studyChart"></canvas>
                </div>
                <p class="text-sm text-gray-500 mt-2">Cards studied per day over the last week</p>
            </div>

            <!-- Achievements -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-semibold mb-4">Achievements</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if (empty($achievements)): ?>
                        <p class="text-gray-600">No achievements unlocked yet. Keep studying!</p>
                    <?php else: ?>
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="flex items-center space-x-4">
                                    <?php if ($achievement['badge_icon']): ?>
                                        <img src="../assets/badges/<?= htmlspecialchars($achievement['badge_icon']) ?>" alt="Badge" class="w-12 h-12">
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="text-lg font-semibold"><?= htmlspecialchars($achievement['name']) ?></h3>
                                        <p class="text-gray-600"><?= htmlspecialchars($achievement['description']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Settings -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Profile Settings</h2>
                <!-- Update Email -->
                <form method="POST" action="profile.php" class="mb-6">
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <?php if (isset($email_error)): ?>
                        <p class="text-red-500 text-sm mb-4"><?= $email_error ?></p>
                    <?php endif; ?>
                    <button type="submit" name="update_email" class="btn-primary">Update Email</button>
                </form>

                <!-- Change Password -->
                <form method="POST" action="profile.php" class="mb-6">
                    <div class="mb-4">
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div class="mb-4">
                        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <?php if (isset($password_error)): ?>
                        <p class="text-red-500 text-sm mb-4"><?= $password_error ?></p>
                    <?php endif; ?>
                    <button type="submit" name="update_password" class="btn-primary">Change Password</button>
                </form>

                <!-- Update Language -->
                <form method="POST" action="profile.php">
                    <div class="mb-4">
                        <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                        <select name="language" id="language" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            <option value="en" <?= $user['language'] === 'en' ? 'selected' : '' ?>>English</option>
                            <option value="es" <?= $user['language'] === 'es' ? 'selected' : '' ?>>Español</option>
                            <option value="fr" <?= $user['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                        </select>
                    </div>
                    <?php if (isset($language_error)): ?>
                        <p class="text-red-500 text-sm mb-4"><?= $language_error ?></p>
                    <?php endif; ?>
                    <button type="submit" name="update_language" class="btn-primary">Update Language</button>
                </form>
            </div>
        </div>
    </main>
    <script>
        // Study Progress Graph
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('studyChart')?.getContext('2d');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: [{
                            label: 'Cards Studied',
                            data: <?= json_encode($data_points) ?>,
                            borderColor: '#3B82F6',
                            tension: 0.4,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>