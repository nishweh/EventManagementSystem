<?php
session_start();
require 'db.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit;
}

// Get current user type
$stmt = $conn->prepare("SELECT user_type FROM users WHERE user_id = ?");
$stmt->execute(array($_SESSION['user_id']));
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($currentUser['user_type'] !== 'Admin') {
    echo "<script>alert('Only admins can manage users.'); window.location.href='profile.php';</script>";
    exit;
}

// Handle form actions
$message = '';
$messageType = '';
$showAddForm = false;

// Search functionality
$searchQuery = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$searchBy = isset($_GET['search_by']) ? $_GET['search_by'] : 'name';
$users = array();

try {
    $query = "SELECT user_id, user_name, user_email, user_type, created_at 
              FROM users 
              WHERE user_type = 'Employee'";
    
    if (!empty($searchQuery)) {
        if ($searchBy === 'email') {
            $query .= " AND user_email LIKE ?";
        } else {
            $query .= " AND user_name LIKE ?";
        }
    }
    
    $stmt = $conn->prepare($query);
    if (!empty($searchQuery)) {
        $stmt->execute(array("%$searchQuery%"));
    } else {
        $stmt->execute();
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching users: " . $e->getMessage();
    $messageType = "error";
}

// Pre-fetch user assignments
$userAssignments = array();
if (!empty($users)) {
    $userIds = array();
    foreach ($users as $user) {
        $userIds[] = $user['user_id'];
    }
    
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $conn->prepare("SELECT p.user_id, e.event_name, p.participant_status, p.participant_role 
                            FROM participants p 
                            JOIN events e ON p.event_id = e.event_id 
                            WHERE p.user_id IN ($placeholders)");
    $stmt->execute($userIds);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($assignments as $assignment) {
        $userId = $assignment['user_id'];
        if (!isset($userAssignments[$userId])) {
            $userAssignments[$userId] = array();
        }
        $userAssignments[$userId][] = $assignment;
    }
}

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    try {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $type = trim($_POST['type']);
        
        // Validation
        if (!$name || !$email || !$type) {
            throw new Exception("All fields are required.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }
        
        if (!in_array($type, array('Admin', 'Employee'))) {
            throw new Exception("Invalid user type selected.");
        }
        
        // Check email uniqueness
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_email = ?");
        $stmt->execute(array($email));
        if ($stmt->fetch()) {
            throw new Exception("Email already registered.");
        }
        
        // Hash password if provided
        $passwordHash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (user_name, user_email, password_hash, user_type) VALUES (?, ?, ?, ?)");
        $stmt->execute(array($name, $email, $passwordHash, $type));
        
        $message = "User created successfully!";
        $messageType = "success";
        $showAddForm = false;
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
        $showAddForm = true;
    }
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $id = $_POST['user_id'];
        $name = trim($_POST['edit_name']);
        $email = trim($_POST['edit_email']);
        $password = trim($_POST['edit_password']);
        $type = trim($_POST['edit_type']);
        
        // Validate required fields
        if (!$name || !$email) {
            throw new Exception("Name and email are required.");
        }
        
        // Build update query
        $updateFields = array("user_name = ?", "user_email = ?");
        $params = array($name, $email);
        
        // Only update password if provided
        if (!empty($password)) {
            $updateFields[] = "password_hash = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $updateFields[] = "user_type = ?";
        $params[] = $type;
        
        $params[] = $id; // Where condition
        
        $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        $message = "User updated successfully!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
    
    // Refresh users list after update
    $stmt = $conn->query("SELECT user_id, user_name, user_email, user_type, created_at FROM users WHERE user_type = 'Employee'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute(array($id));
        $message = "User deleted successfully!";
        $messageType = "success";
        
    } catch (PDOException $e) {
        $message = "Error deleting user: " . $e->getMessage();
        $messageType = "error";
    }
    
    // Refresh users list after deletion
    $stmt = $conn->query("SELECT user_id, user_name, user_email, user_type, created_at FROM users WHERE user_type = 'Employee'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="main.css">
    <style>
    body {
        background-color: #1e1e1e;
        color: #f0f0f0;
        font-family: 'Inter', sans-serif;
        margin: 0;
        padding: 0;
    }
    h1 {
        text-align: center;
        font-size: 36px;
        font-weight: 800;
        color: #d3d3d3;
        margin-bottom: 40px;
    }
    .search-bar {
        position: sticky;
        top: 40px;
        background: rgba(255, 255, 255, 0.07);
        padding: 20px;
        border-radius: 24px;
        box-shadow: 0 12px 28px rgba(50, 50, 50, 0.4);
        margin-bottom: 30px;
        z-index: 100;
    }
    .search-form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
    }
    .search-form input[type="text"],
    .search-form select {
        padding: 12px;
        border-radius: 14px;
        border: none;
        background-color: rgba(255, 255, 255, 0.15);
        color: #fff;
        width: 250px;
        min-width: 200px;
    }
    .search-form input[type="text"]::placeholder {
        color: #fff;
        opacity: 1;
    }
    .search-form select {
        background-color: #2e2e2e;
        color: #ffffff;
    }
    .search-form button {
        background: linear-gradient(145deg, #3a3a3a, #555555);
        border: none;
        padding: 12px 24px;
        font-weight: 700;
        font-size: 16px;
        color: #f0f0f0;
        border-radius: 16px;
        cursor: pointer;
        box-shadow: 0 8px 22px rgba(80, 80, 80, 0.8);
    }
    .search-form button:hover {
        background: linear-gradient(145deg, #555555, #777777);
    }
    .actions {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
    }
    .actions button {
        background: linear-gradient(145deg, #3a3a3a, #555555);
        border: none;
        padding: 12px 24px;
        font-weight: 700;
        font-size: 16px;
        color: #f0f0f0;
        border-radius: 16px;
        cursor: pointer;
        box-shadow: 0 8px 22px rgba(80, 80, 80, 0.8);
        transition: all 0.2s ease-in-out;
    }
    .actions button:hover {
        background: linear-gradient(145deg, #555555, #777777);
        transform: translateY(-2px);
    }
    .user-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 40px;
        background-color: rgba(255, 255, 255, 0.07);
        border-radius: 16px;
        overflow: hidden;
    }
    .user-table th,
    .user-table td {
        padding: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-align: left;
    }
    .user-table th {
        background-color: rgba(255, 255, 255, 0.1);
        font-weight: 600;
    }
    .user-table tr:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    .form-box {
        background: rgba(255, 255, 255, 0.07);
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 12px 28px rgba(50, 50, 50, 0.4);
        margin-bottom: 30px;
    }
    .form-box label {
        font-weight: 600;
        font-size: 15px;
        color: #d0d0d0;
        display: block;
        margin-top: 20px;
    }
    .form-box input,
    .form-box textarea,
    .form-box select {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 14px;
        margin-top: 8px;
        font-size: 16px;
    }
    .form-box input,
    .form-box textarea {
        background-color: rgba(255, 255, 255, 0.15);
        color: #fff;
    }
    .form-box select {
        background-color: #2e2e2e;
        color: #ffffff;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg%20fill%3D'%23ffffff'%20height%3D'24'%20width%3D'24'%20viewBox%3D'0%200%2024%2024'%3E%3Cpath%20d%3D'M7%2010l5%205%205-5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        background-size: 18px 18px;
    }
    .form-box .submit-btn {
        margin-top: 30px;
        background: linear-gradient(145deg, #3a3a3a, #555555);
        border: none;
        padding: 14px 28px;
        font-weight: 700;
        font-size: 16px;
        color: #f0f0f0;
        border-radius: 16px;
        cursor: pointer;
        box-shadow: 0 8px 22px rgba(80, 80, 80, 0.8);
        transition: all 0.2s ease-in-out;
    }
    .form-box .submit-btn:hover {
        background: linear-gradient(145deg, #555555, #777777);
        transform: translateY(-2px);
    }
    .message {
        text-align: center;
        margin: 20px 0;
        font-weight: 600;
        padding: 15px;
        border-radius: 12px;
    }
    .error {
        background-color: #ef5350;
        color: white;
    }
    .success {
        background-color: #66bb6a;
        color: white;
    }
    .back-link {
        display: block;
        text-align: center;
        margin-top: 40px;
        color: #cccccc;
        text-decoration: none;
    }
    .back-link:hover {
        text-decoration: underline;
    }
    .hidden {
        display: none;
    }
    .main-btn {
        background: linear-gradient(145deg, #3a3a3a, #555555);
        border: none;
        padding: 12px 24px;
        font-weight: 700;
        font-size: 16px;
        color: #f0f0f0;
        border-radius: 16px;
        cursor: pointer;
        box-shadow: 0 8px 22px rgba(80, 80, 80, 0.8);
        transition: all 0.2s ease-in-out;
    }
    .main-btn:hover {
        background: linear-gradient(145deg, #555555, #777777);
        transform: translateY(-2px);
    }
    .danger-btn {
        background: #e53935;
    }
    .danger-btn:hover {
        background: #f44336;
    }
    .cancel-btn {
        background: #e53935;
    }
    .cancel-btn:hover {
        background: #f44336;
    }
    body {
        background: linear-gradient(145deg, #2a2a2a, #3e3e3e) !important;
    }
    .user-name-link {
        color: #cccccc;
        cursor: pointer;
        text-decoration: underline;
        font-weight: 600;
    }
    .user-name-link:hover {
        color: #eeeeee;
    }
    .expandable {
        background-color: rgba(255, 255, 255, 0.05);
        padding: 16px;
        margin: 10px -16px -16px -16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    .assignment-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .assignment-table th,
    .assignment-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .assignment-table th {
        background-color: rgba(255, 255, 255, 0.08);
        font-weight: 600;
    }
    .no-assignments {
        color: #aaaaaa;
        font-style: italic;
        padding: 10px;
    }
</style>

</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <h1>User Management</h1>

        <?php if ($message): ?>
            <div class="message <?= $messageType === 'error' ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search_query" placeholder="Search by name or email..." 
                       value="<?= htmlspecialchars($searchQuery) ?>">
                <select name="search_by">
                    <option value="name" <?= $searchBy === 'name' ? 'selected' : '' ?>>Search by Name</option>
                    <option value="email" <?= $searchBy === 'email' ? 'selected' : '' ?>>Search by Email</option>
                </select>
                <button type="submit">Search</button>
                <?php if ($searchQuery): ?>
                    <button type="button" onclick="window.location.href='<?= strtok($_SERVER["REQUEST_URI"], '?') ?>'">Clear</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Actions -->
        <div class="actions">
            <button onclick="toggleAddForm()">Add New User</button>
        </div>

        <!-- Add User Form -->
        <div id="addUserForm" class="<?= $showAddForm ? '' : 'hidden' ?> form-box">
            <h2 style="text-align: center; margin-top: 0;">Create New User</h2>
            <form action="" method="POST">
                <input type="hidden" name="action" value="create">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
                <label for="password">Password</label>
                <input type="password" id="password" name="password">
                <label for="type">User Type</label>
                <select id="type" name="type" required>
                    <option value="">-- Select Type --</option>
                    <option value="Admin">Admin</option>
                    <option value="Employee">Employee</option>
                </select>
                <div style="text-align: center;">
                    <button type="submit" class="submit-btn">Create User</button>
                    <button type="button" class="main-btn cancel-btn" onclick="toggleAddForm()" style="margin-left: 10px;">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <table class="user-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr id="user-row-<?= $user['user_id'] ?>">
                        <td>
                            <span class="user-name-link" onclick="toggleUserDetails(<?= $user['user_id'] ?>)">
                                <?= htmlspecialchars($user['user_name']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($user['user_email']) ?></td>
                        <td><?= htmlspecialchars($user['user_type']) ?></td>
                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <button class="main-btn" onclick="toggleEditForm(<?= $user['user_id'] ?>)">Edit</button>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="delete_id" value="<?= $user['user_id'] ?>">
                                    <button type="submit" class="main-btn danger-btn">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Event Assignments Row -->
                    <tr id="user-assignments-<?= $user['user_id'] ?>" class="hidden">
                        <td colspan="5">
                            <div class="expandable">
                                <h3>Events Assigned</h3>
                                <?php if (!empty($userAssignments[$user['user_id']])): ?>
                                    <table class="assignment-table">
                                        <thead>
                                            <tr>
                                                <th>Event Name</th>
                                                <th>Status</th>
                                                <th>Role</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($userAssignments[$user['user_id']] as $assignment): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($assignment['event_name']) ?></td>
                                                    <td><?= htmlspecialchars($assignment['participant_status']) ?></td>
                                                    <td><?= htmlspecialchars($assignment['participant_role']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="no-assignments">No event assignments found</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Edit Form Row -->
                    <tr id="edit-form-<?= $user['user_id'] ?>" class="hidden">
                        <td colspan="5">
                            <div class="form-box" style="margin: 0;">
                                <h2 style="text-align: center; margin-top: 0;">Edit User</h2>
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                    <label for="edit_name">Name</label>
                                    <input type="text" id="edit_name" name="edit_name" 
                                           value="<?= htmlspecialchars($user['user_name']) ?>" required>
                                    <label for="edit_email">Email</label>
                                    <input type="email" id="edit_email" name="edit_email" 
                                           value="<?= htmlspecialchars($user['user_email']) ?>" required>
                                    <label for="edit_password">New Password (optional)</label>
                                    <input type="password" id="edit_password" name="edit_password">
                                    <label for="edit_type">User Type</label>
                                    <select id="edit_type" name="edit_type" required>
                                        <option value="Admin" <?= $user['user_type'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="Employee" <?= $user['user_type'] === 'Employee' ? 'selected' : '' ?>>Employee</option>
                                    </select>
                                    <div style="text-align: center; margin-top: 20px;">
                                        <button type="submit" class="main-btn submit-btn">Update</button>
                                        <button type="button" class="main-btn cancel-btn" onclick="toggleEditForm(<?= $user['user_id'] ?>)" style="margin-left: 10px;">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="profile.php" class="back-link">← Back to Dashboard</a>
    </main>
    <script>
        function toggleAddForm() {
            const form = document.getElementById('addUserForm');
            form.classList.toggle('hidden');
        }

        function toggleEditForm(userId) {
            const row = document.getElementById('user-row-' + userId);
            const formRow = document.getElementById('edit-form-' + userId);
            
            row.style.display = row.style.display === 'none' ? '' : 'none';
            formRow.classList.toggle('hidden');
        }
        
        function toggleUserDetails(userId) {
            const assignmentsRow = document.getElementById('user-assignments-' + userId);
            assignmentsRow.classList.toggle('hidden');
        }
    </script>
</body>
</html>