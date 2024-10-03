<?php
$host = 'localhost';
$db = 'todo_db';
$user = 'root'; 
$pass = ''; 

// Create a connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle adding tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['task_name'])) {
        $task_name = $_POST['task_name'];
        // Insert new task with is_completed defaulting to 0
        $stmt = $conn->prepare("INSERT INTO tasks (task_name, is_completed) VALUES (?, 0)");
        $stmt->bind_param("s", $task_name);
        $stmt->execute();

        // Get the ID of the newly inserted task
        $taskId = $stmt->insert_id;
        $stmt->close();

        // Return the new task as JSON
        echo json_encode(['success' => true, 'task' => ['id' => $taskId, 'task_name' => $task_name, 'is_completed' => 0]]);
    } elseif (isset($_POST['action']) && $_POST['action'] === 'complete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE tasks SET is_completed = NOT is_completed WHERE id = $id");
        echo json_encode(['success' => true]);
    }
    exit; // Prevent further execution
}

// Fetch all tasks
$result = $conn->query("SELECT * FROM tasks ORDER BY created_at DESC");
$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

$conn->close();
?>
