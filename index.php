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

// Handle completing and deleting tasks
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'complete') {
        $conn->query("UPDATE tasks SET is_completed = NOT is_completed WHERE id = $id");
    } elseif ($_GET['action'] === 'delete') {
        $conn->query("DELETE FROM tasks WHERE id = $id");
    }
}

// Fetch all tasks
$result = $conn->query("SELECT * FROM tasks ORDER BY created_at DESC");
$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">To-Do List</h1>
        <form id="task-form" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" id="task-name" placeholder="Enter new task" required>
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Add Task</button>
                </div>
            </div>
        </form>
        <div id="alert-message" class="alert alert-success"></div>
        <div class="row" id="task-list"></div> 
        <button id="toggle-completed" class="btn btn-secondary mt-3">Pin Completed Tasks</button>
        <div class="spinner-border text-primary" role="status" id="loading-spinner">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    
    <script>
    const tasks = <?php echo json_encode($tasks); ?>;
    const taskList = document.getElementById('task-list');
    const alertMessage = document.getElementById('alert-message');
    const loadingSpinner = document.getElementById('loading-spinner');
    let showCompleted = true; // State to track showing/hiding completed tasks

    function displayTasks() {
        taskList.innerHTML = '';
        tasks.forEach(task => {
            // Only display completed tasks if showCompleted is true
            if (showCompleted || !task.is_completed) {
                const col = document.createElement('div');
                col.className = 'col-12 col-sm-6 col-md-4 mb-3'; // Responsive columns
                col.innerHTML = `
                    <div class="card ${task.is_completed ? 'bg-light' : 'bg-white'} border-primary task-item">
                        <div class="card-body">
                            <h5 class="card-title ${task.is_completed ? 'completed' : ''}" style="text-decoration: ${task.is_completed ? 'underline' : 'none'};">${task.task_name}</h5>
                            <p class="card-text"><small class="text-muted">${new Date(task.created_at).toLocaleString()}</small></p>
                            <div>
                                <button class="btn btn-sm btn-success" onclick="toggleCompletion(${task.id})">✔️</button>
                                <a href="?action=delete&id=${task.id}" class="btn btn-sm btn-danger">❌</a>
                            </div>
                        </div>
                    </div>
                `;
                taskList.appendChild(col);
            }
        });
    }

    // Toggle task completion
    function toggleCompletion(taskId) {
        loadingSpinner.style.display = 'block'; // Show loading spinner
        fetch('tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=complete&id=${taskId}`,
        }).then(response => response.json()).then(data => {
            if (data.success) {
                displayTasks(); // Refresh the task list
                showAlert('Task completion toggled successfully!'); // Show success message
            } else {
                showAlert('Failed to toggle task completion.'); // Show error message
            }
        }).finally(() => {
            loadingSpinner.style.display = 'none'; // Hide loading spinner
        });
    }

    // Show alert message
    function showAlert(message) {
        alertMessage.textContent = message;
        alertMessage.style.display = 'block';
        setTimeout(() => {
            alertMessage.style.display = 'none';
        }, 3000);
    }

    // Handle task form submit
    document.getElementById('task-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const taskName = document.getElementById('task-name').value;
        loadingSpinner.style.display = 'block'; // Show loading spinner
        fetch('tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `task_name=${encodeURIComponent(taskName)}`,
        }).then(response => response.json()).then(data => {
            if (data.success) {
                tasks.push(data.task); // Add the new task to the tasks array
                displayTasks();
                showAlert('Task added successfully!'); // Show success message
                document.getElementById('task-name').value = '';
            } else {
                showAlert('Failed to add task.'); // Show error message
            }
        }).finally(() => {
            loadingSpinner.style.display = 'none'; // Hide loading spinner
        });
    });

    // Toggle completed tasks visibility
    document.getElementById('toggle-completed').addEventListener('click', function() {
        showCompleted = !showCompleted; // Toggle the state
        this.textContent = showCompleted ? "Hide Completed Tasks" : "Show Completed Tasks"; // Update button text
        displayTasks(); // Refresh the task list
    });

    // Initial display of tasks
    displayTasks();
    </script>

</body>
</html>
