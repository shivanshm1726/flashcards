<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch categories for the filter form
try {
  $stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name ASC");
  $stmt->execute();
  $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Error fetching categories: " . $e->getMessage());
  $categories = [];
  $error = "Failed to load categories. Please try again later.";
}

// Handle filters and sorting
$selected_categories = $_POST['categories'] ?? [];
$selected_status = $_POST['status'] ?? 'all'; // 'all', 'completed', 'incomplete'
$sort_by = $_POST['sort_by'] ?? 'none'; // 'none', 'created_at', 'last_studied'
$sort_order = $_POST['sort_order'] ?? 'DESC'; // 'ASC' or 'DESC'

// Build the WHERE clause
$where_clause = "WHERE d.user_id = ?";
$params = [$user_id];

// Filter by categories
if (!empty($selected_categories)) {
  $selected_categories = array_map('intval', $selected_categories);
  $placeholders = implode(',', array_fill(0, count($selected_categories), '?'));
  $where_clause .= " AND d.category_id IN ($placeholders)";
  $params = array_merge($params, $selected_categories);
}

// Filter by status (completed/incomplete)
if ($selected_status !== 'all') {
  $where_clause .= " AND d.is_mastered = ?";
  $params[] = $selected_status === 'completed' ? 1 : 0;
}

// Build the ORDER BY clause (only if sorting is selected)
$order_clause = '';
if ($sort_by !== 'none') {
  $sort_column = $sort_by === 'last_studied' ? 'd.last_studied' : 'd.created_at';
  $sort_direction = $sort_order === 'ASC' ? 'ASC' : 'DESC';
  $order_clause = "ORDER BY $sort_column $sort_direction";
  // Handle NULL values in last_studied (place them at the end)
  if ($sort_by === 'last_studied') {
    $order_clause = "ORDER BY d.last_studied IS NULL, $sort_column $sort_direction";
  }
}

// Fetch decks based on the filters and sorting
try {
  $stmt = $pdo->prepare("
        SELECT d.id, d.title, d.description, d.created_at, d.last_studied, d.reminder_interval, d.is_mastered, c.name AS category_name
        FROM decks d
        LEFT JOIN categories c ON d.category_id = c.id
        $where_clause
        $order_clause
    ");
  $stmt->execute($params);
  $decks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Error fetching decks: " . $e->getMessage());
  $decks = [];
  $error = "Failed to load decks. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Decks</title>
  <link href="../src/output.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
  <header class="bg-blue-600 text-white p-6 shadow-lg">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
      <h1 class="text-2xl font-bold">View Decks</h1>
      <div class="space-x-4">
        <a href="create_deck.php" class="text-sm text-white hover:underline">Create Deck</a>
        <a href="index.php" class="text-sm text-white hover:underline">Back to Dashboard</a>
      </div>
    </div>
  </header>
  <main class="p-6">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row gap-6">
      <!-- Sidebar: Filter Section -->
      <aside class="w-full md:w-1/4 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4">Filters</h2>
        <?php if (isset($error)): ?>
          <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="view_decks.php">
          <!-- Category Filter -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-700 mb-2">Category</h3>
            <div class="space-y-2">
              <?php foreach ($categories as $category): ?>
                <label class="flex items-center">
                  <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>"
                    <?php echo in_array($category['id'], $selected_categories) ? 'checked' : ''; ?>
                    class="form-checkbox h-5 w-5 text-blue-600">
                  <span class="ml-2 text-gray-700"><?php echo htmlspecialchars($category['name']); ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Status Filter -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-700 mb-2">Status</h3>
            <div class="space-y-2">
              <label class="flex items-center">
                <input type="radio" name="status" value="all"
                  <?php echo $selected_status === 'all' ? 'checked' : ''; ?>
                  class="form-radio h-5 w-5 text-blue-600">
                <span class="ml-2 text-gray-700">All</span>
              </label>
              <label class="flex items-center">
                <input type="radio" name="status" value="completed"
                  <?php echo $selected_status === 'completed' ? 'checked' : ''; ?>
                  class="form-radio h-5 w-5 text-blue-600">
                <span class="ml-2 text-gray-700">Completed</span>
              </label>
              <label class="flex items-center">
                <input type="radio" name="status" value="incomplete"
                  <?php echo $selected_status === 'incomplete' ? 'checked' : ''; ?>
                  class="form-radio h-5 w-5 text-blue-600">
                <span class="ml-2 text-gray-700">Incomplete</span>
              </label>
            </div>
          </div>

          <!-- Sort By -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-700 mb-2">Sort By</h3>
            <select name="sort_by" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              <option value="none" <?php echo $sort_by === 'none' ? 'selected' : ''; ?>>None</option>
              <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Created Date</option>
              <option value="last_studied" <?php echo $sort_by === 'last_studied' ? 'selected' : ''; ?>>Last Studied</option>
            </select>
          </div>

          <!-- Sort Order -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-700 mb-2">Sort Order</h3>
            <div class="space-y-2">
              <label class="flex items-center">
                <input type="radio" name="sort_order" value="ASC"
                  <?php echo $sort_order === 'ASC' ? 'checked' : ''; ?>
                  class="form-radio h-5 w-5 text-blue-600">
                <span class="ml-2 text-gray-700">Ascending</span>
              </label>
              <label class="flex items-center">
                <input type="radio" name="sort_order" value="DESC"
                  <?php echo $sort_order === 'DESC' ? 'checked' : ''; ?>
                  class="form-radio h-5 w-5 text-blue-600">
                <span class="ml-2 text-gray-700">Descending</span>
              </label>
            </div>
          </div>

          <div class="flex space-x-4">
            <button type="submit" class="btn-primary">Apply Filters</button>
            <a href="view_decks.php" class="text-sm text-blue-600 hover:underline self-center">Clear Filters</a>
          </div>
        </form>
      </aside>

      <!-- Decks List -->
      <div class="w-full md:w-3/4">
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h2 class="text-xl font-semibold mb-4">Your Decks</h2>
          <?php if (empty($decks)): ?>
            <p class="text-gray-600">No decks found. Create a new deck to get started!</p>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <?php foreach ($decks as $deck): ?>
                <div class="bg-blue-50 p-4 rounded-lg relative">
                  <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($deck['title']); ?></h3>
                  <p class="text-gray-600"><?php echo htmlspecialchars($deck['description'] ?? 'No description'); ?></p>
                  <p class="text-sm text-gray-500 mt-2">
                    Category: <?php echo htmlspecialchars($deck['category_name'] ?? 'None'); ?>
                  </p>
                  <p class="text-sm text-gray-500">
                    Created: <?php echo date('M j, Y', strtotime($deck['created_at'])); ?>
                  </p>
                  <p class="text-sm text-gray-500">
                    Last Studied: <?php echo $deck['last_studied'] ? date('M j, Y', strtotime($deck['last_studied'])) : 'Never'; ?>
                  </p>
                  <div class="mt-4 flex justify-between items-center">
                    <a href="study.php?deck_id=<?php echo $deck['id']; ?>" class="btn-primary">Study</a>
                    <?php if ($deck['is_mastered']): ?>
                      <span class="text-green-600">
                        <svg class="w-6 h-6 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Completed
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</body>

</html>