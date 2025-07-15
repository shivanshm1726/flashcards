<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Generate CSRF token for form security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch categories from the database
try {
    $stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
    $error = "Failed to load categories. Please try again later.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token. Please try again.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = $_POST['category_id'] ?? '';

        // Validate inputs
        if (empty($title)) {
            $error = "Deck title is required.";
        } elseif (strlen($title) > 100) {
            $error = "Deck title must be 100 characters or less.";
        } else {
            // Set category_id to NULL if "No Category" is selected
            $category_id = $category_id === '' ? null : (int)$category_id;

            // Insert the new deck
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO decks (user_id, title, description, category_id, created_at, reminder_interval, is_mastered)
                    VALUES (?, ?, ?, ?, NOW(), 7, 0)
                ");
                $stmt->execute([$user_id, $title, $description, $category_id]);

                // Get the ID of the newly created deck
                $deck_id = $pdo->lastInsertId();

                // Redirect to add_flashcards.php with the deck_id
                header("Location: add_flashcards.php?deck_id=" . $deck_id);
                exit();
            } catch (Exception $e) {
                error_log("Error creating deck: " . $e->getMessage());
                $error = "Failed to create deck. Please try again.";
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
    <title>Create Deck</title>
    <link href="../src/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/gsap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/ScrollTrigger.min.js" defer></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
            color: #374151;
            min-height: 100vh;
        }
        .header-bg {
            background: linear-gradient(120deg, #3b82f6, #8b5cf6);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: #8b5cf6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: background 0.3s ease, transform 0.3s ease;
            display: inline-block;
        }
        .btn-primary:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }
        .btn-secondary {
            @apply text-blue-600 hover:text-blue-800 transition-all duration-300;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            border-radius: 8px;
            padding: 0.75rem;
            color: #ef4444;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-bg text-white p-6 sticky top-0 z-20">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <h1 class="text-3xl font-bold tracking-wide">Create Deck</h1>
            <div class="flex space-x-6">
                <a href="index.php" class="btn-secondary flex items-center"><i class="fas fa-arrow-left mr-2"></i> Dashboard</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-6">
        <div class="max-w-md mx-auto form-container p-6 animated-section">
            <?php if (isset($error)): ?>
                <p class="error-message mb-4"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="POST" action="create_deck.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700"><i class="fas fa-heading mr-2"></i> Deck Title</label>
                    <input type="text" name="title" id="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-gray-900 placeholder-gray-400" required>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700"><i class="fas fa-align-left mr-2"></i> Deck Description</label>
                    <textarea name="description" id="description" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-gray-900 placeholder-gray-400"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                <div class="mb-4">
                    <label for="category_id" class="block text-sm font-medium text-gray-700"><i class="fas fa-folder mr-2"></i> Category</label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-gray-900">
                        <option value="">No Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary"><i class="fas fa-plus mr-2"></i> Create Deck</button>
            </form>
        </div>
    </main>

    <!-- GSAP Animations -->
    <script>
        gsap.registerPlugin(ScrollTrigger);
        gsap.utils.toArray('.animated-section').forEach(section => {
            gsap.from(section, {
                scrollTrigger: {
                    trigger: section,
                    start: 'top 85%',
                    toggleActions: 'play none none none',
                    once: true
                },
                y: 30,
                opacity: 0,
                duration: 0.6,
                ease: 'power2.out'
            });
        });
    </script>
</body>
</html>