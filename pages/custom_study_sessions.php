<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Study</title>
    <link href="../src/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Create Custom Study Session</h1>
            <a href="index.php" class="text-sm hover:underline">Back to Dashboard</a>
        </div>
    </header>
    <main class="p-4">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <form action="start_custom_study.php" method="POST" class="space-y-4">
                    <div>
                        <label for="session_name" class="block text-sm font-medium text-gray-700">Session Name</label>
                        <input type="text" id="session_name" name="session_name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="time_limit" class="block text-sm font-medium text-gray-700">Time Limit (minutes)</label>
                        <input type="number" id="time_limit" name="time_limit" min="1" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <button type="submit" name="proceed_to_select_decks" class="bg-blue-700 text-white px-4 py-2 rounded-md hover:bg-blue-800">Next: Select Decks</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>