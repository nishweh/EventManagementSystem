<?php
session_start();
require "db.php";
if (!isset($_SESSION["user_id"])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit();
}
// Get current user type
$stmt = $conn->prepare("SELECT user_type FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
if ($currentUser["user_type"] !== "Admin") {
    echo "<script>alert('Only admins can manage events.'); window.location.href='profile.php';</script>";
    exit();
}
// Handle form actions
$message = "";
$messageType = "";
$showAddForm = false;
// Search functionality
$searchQuery = isset($_GET["search_query"]) ? $_GET["search_query"] : "";
$searchBy = isset($_GET["search_by"]) ? $_GET["search_by"] : "name";
$events = [];
try {
    // Base query
    $query = "SELECT e.event_id, e.event_name, e.event_date, e.event_time, e.event_venue, 
    e.event_status, e.event_description, 
    COUNT(DISTINCT u.user_id) AS staff_count,
    (SELECT COUNT(*) FROM feedback f WHERE f.event_id = e.event_id) AS feedback_count
    FROM events e
    LEFT JOIN participants p ON e.event_id = p.event_id
    LEFT JOIN users u ON p.user_id = u.user_id";
    // Add filters
    if (!empty($searchQuery)) {
        if ($searchBy === "venue") {
            $query .= " WHERE e.event_venue LIKE ?";
        } else {
            $query .= " WHERE e.event_name LIKE ?";
        }
    }
    // Group by event
    $query .= " GROUP BY e.event_id ORDER BY e.event_date DESC";
    // Prepare and execute
    $stmt = $conn->prepare($query);
    if (!empty($searchQuery)) {
        if ($searchBy === "venue") {
            $stmt->execute(["%$searchQuery%"]);
        } else {
            $stmt->execute(["%$searchQuery%"]);
        }
    } else {
        $stmt->execute();
    }
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching events: " . $e->getMessage();
    $messageType = "error";
}
// Handle event creation
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "create"
) {
    try {
        $name = trim($_POST["name"]);
        $date = $_POST["date"];
        $time = $_POST["time"];
        $venue = trim($_POST["venue"]);
        $description = trim($_POST["description"]);

        $status = isset($_POST["status"]) ? trim($_POST["status"]) : "Upcoming";

        if (
            !$name ||
            !$date ||
            !$time ||
            !$venue ||
            !$description ||
            !$status
        ) {
            throw new Exception("All fields are required.");
        }

        $stmt = $conn->prepare("INSERT INTO events (event_name, event_date, event_time, 
                               event_venue, event_description, event_status, user_id) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name,
            $date,
            $time,
            $venue,
            $description,
            $status,
            $_SESSION["user_id"],
        ]);
        $message = "Event created successfully!";
        $messageType = "success";
        $showAddForm = false;

        // Refresh events list
        $stmt = $conn->query("SELECT e.event_id, e.event_name, e.event_date, e.event_time, 
        e.event_venue, e.event_status, e.event_description,
        COUNT(DISTINCT u.user_id) AS staff_count,
        (SELECT COUNT(*) FROM feedback f WHERE f.event_id = e.event_id) AS feedback_count
        FROM events e
        LEFT JOIN participants p ON e.event_id = p.event_id
        LEFT JOIN users u ON p.user_id = u.user_id
        GROUP BY e.event_id ORDER BY e.event_date DESC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
        $showAddForm = true;
    }
}
// Handle event update
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "update"
) {
    try {
        $id = $_POST["event_id"];
        $name = trim($_POST["edit_name"]);
        $date = $_POST["edit_date"];
        $time = $_POST["edit_time"];
        $venue = trim($_POST["edit_venue"]);
        $description = trim($_POST["edit_description"]);
        $status = trim($_POST["edit_status"]);
        if (
            !$name ||
            !$date ||
            !$time ||
            !$venue ||
            !$description ||
            !$status
        ) {
            throw new Exception("All fields are required.");
        }
        $stmt = $conn->prepare("UPDATE events SET event_name = ?, event_date = ?, 
                               event_time = ?, event_venue = ?, event_description = ?, 
                               event_status = ? WHERE event_id = ?");
        $stmt->execute([
            $name,
            $date,
            $time,
            $venue,
            $description,
            $status,
            $id,
        ]);
        $message = "Event updated successfully!";
        $messageType = "success";
        // Refresh events list
        $stmt = $conn->query("SELECT e.event_id, e.event_name, e.event_date, e.event_time, 
        e.event_venue, e.event_status, e.event_description,
        COUNT(DISTINCT u.user_id) AS staff_count,
        (SELECT COUNT(*) FROM feedback f WHERE f.event_id = e.event_id) AS feedback_count
        FROM events e
        LEFT JOIN participants p ON e.event_id = p.event_id
        LEFT JOIN users u ON p.user_id = u.user_id
        GROUP BY e.event_id ORDER BY e.event_date DESC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}
// Handle event deletion
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "delete"
) {
    try {
        $id = $_POST["delete_id"];
        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->execute([$id]);
        $message = "Event deleted successfully!";
        $messageType = "success";
        // Refresh events list
        $stmt = $conn->query("SELECT e.event_id, e.event_name, e.event_date, e.event_time, 
        e.event_venue, e.event_status, e.event_description,
        COUNT(DISTINCT u.user_id) AS staff_count,
        (SELECT COUNT(*) FROM feedback f WHERE f.event_id = e.event_id) AS feedback_count
        FROM events e
        LEFT JOIN participants p ON e.event_id = p.event_id
        LEFT JOIN users u ON p.user_id = u.user_id
        GROUP BY e.event_id ORDER BY e.event_date DESC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Error deleting event: " . $e->getMessage();
        $messageType = "error";
    }
}
// Handle single staff assignment
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["user_id"]) &&
    $_POST["user_id"] !== "all"
) {
    try {
        $event_id = $_POST["event_id"];
        $user_id = $_POST["user_id"];
        $role = $_POST["role"];

        // Prevent duplicate
        $stmt = $conn->prepare(
            "SELECT * FROM participants WHERE user_id = ? AND event_id = ?"
        );
        $stmt->execute([$user_id, $event_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("This staff is already assigned.");
        }

        $stmt = $conn->prepare(
            "INSERT INTO participants (user_id, event_id, participant_role, participant_status) VALUES (?, ?, ?, 'Assigned')"
        );
        $stmt->execute([$user_id, $event_id, $role]);

        $message = "Staff assigned successfully!";
        $messageType = "success";

        // Refresh events list to reflect changes
        $stmt = $conn->query("SELECT e.event_id, e.event_name, e.event_date, e.event_time, 
        e.event_venue, e.event_status, e.event_description,
        COUNT(DISTINCT u.user_id) AS staff_count,
        (SELECT COUNT(*) FROM feedback f WHERE f.event_id = e.event_id) AS feedback_count
        FROM events e
        LEFT JOIN participants p ON e.event_id = p.event_id
        LEFT JOIN users u ON p.user_id = u.user_id
        GROUP BY e.event_id ORDER BY e.event_date DESC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $messageType = "error";
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}
// Handle staff unassignment
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "unassign"
) {
    try {
        $user_id = $_POST["user_id"];
        $event_id = $_POST["event_id"];

        if ($user_id == $_SESSION["user_id"]) {
            throw new Exception("You cannot unassign yourself from an event.");
        }

        $stmt = $conn->prepare(
            "DELETE FROM participants WHERE user_id = ? AND event_id = ?"
        );
        $stmt->execute([$user_id, $event_id]);

        $message = "Staff unassigned successfully!";
        $messageType = "success";

        // Refresh events list
        $stmt = $conn->query("SELECT e.event_id, e.event_name, e.event_date, e.event_time, 
        e.event_venue, e.event_status, e.event_description,
        COUNT(DISTINCT u.user_id) AS staff_count,
        (SELECT COUNT(*) FROM feedback f WHERE f.event_id = e.event_id) AS feedback_count
        FROM events e
        LEFT JOIN participants p ON e.event_id = p.event_id
        LEFT JOIN users u ON p.user_id = u.user_id
        GROUP BY e.event_id ORDER BY e.event_date DESC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Error unassigning staff: " . $e->getMessage();
        $messageType = "error";
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}
// Handle feedback deletion
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "delete_feedback"
) {
    try {
        $id = $_POST["feedback_id"];
        $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ?");
        $stmt->execute([$id]);
        $message = "Feedback deleted successfully!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Error deleting feedback: " . $e->getMessage();
        $messageType = "error";
    }
}

if (isset($_POST["user_id"]) && $_POST["user_id"] === "all") {
    $event_id = $_POST["event_id"];
    $role = $_POST["role"];
    $stmt = $conn->query(
        "SELECT user_id FROM users WHERE user_type = 'Employee'"
    );
    $all_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($all_users)) {
        $placeholders = implode(
            ",",
            array_fill(0, count($all_users), '(?, ?, ?, "Assigned")')
        );
        $values = [];
        foreach ($all_users as $uid) {
            $values[] = $uid;
            $values[] = $event_id;
            $values[] = $role;
        }
        $sql = "INSERT INTO participants (user_id, event_id, participant_role, participant_status) VALUES $placeholders";
        $stmt = $conn->prepare($sql);
        $stmt->execute($values);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Events</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="main.css">
    <style>
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
    .event-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 40px;
        background-color: rgba(255, 255, 255, 0.07);
        border-radius: 16px;
        overflow: hidden;
    }
    .event-table th,
    .event-table td {
        padding: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-align: left;
    }
    .event-table th {
        background-color: rgba(255, 255, 255, 0.1);
        font-weight: 600;
    }
    .event-table tr:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    .form-box {
        background: rgba(255, 255, 255, 0.07);
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 12px 28px rgba(80, 80, 80, 0.4);
        margin-bottom: 30px;
    }
    .form-box label {
        font-weight: 600;
        font-size: 15px;
        color: #cccccc;
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
    .expandable {
        background-color: rgba(255, 255, 255, 0.05);
        padding: 16px;
        margin: 10px -16px -16px -16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    .feedback-table {
        width: 100%;
        border-collapse: collapse;
    }
    .feedback-table th,
    .feedback-table td {
        padding: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-align: left;
    }
    .feedback-table th {
        background-color: rgba(255, 255, 255, 0.05);
        color: #cccccc;
    }
    .feedback-table .btn-delete {
        background: #e53935;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
    }
    .feedback-table .btn-delete:hover {
        background: #c62828;
    }
    .event-name-link {
        color: #cccccc;
        cursor: pointer;
        text-decoration: underline;
    }
    .event-name-link:hover {
        color: #eeeeee;
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
    .main-btn.danger-btn,
    .main-btn.cancel-btn {
        background: #e53935;
        color: #fff;
        box-shadow: 0 8px 22px rgba(229, 57, 53, 0.5);
    }
    .main-btn.danger-btn:hover,
    .main-btn.cancel-btn:hover {
        background: #b71c1c;
    }
    .assign-select {
        padding: 12px;
        border-radius: 14px;
        border: none;
        background-color: rgba(255, 255, 255, 0.15);
        color: #fff;
        min-width: 200px;
        margin-right: 10px;
    }
    .assign-select:focus {
        outline: 2px solid #555555;
    }
    .assign-select option {
        color: #000;
        background: #fff;
    }
    .search-form input[type="text"]::placeholder {
        color: #fff;
        opacity: 1;
    }body {
    background: linear-gradient(145deg, #2a2a2a, #3e3e3e) !important;
}
</style>
</head>
<body>
    <?php include "sidebar.php"; ?>
    <main class="main-content">
        <h1>Event Management</h1>
        <?php if ($message): ?>
            <div class="message <?= $messageType === "error"
                ? "error"
                : "success" ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search_query" placeholder="Search by name or venue..." 
                       value="<?= htmlspecialchars($searchQuery) ?>">
                <select name="search_by">
                    <option value="name" <?= $searchBy === "name"
                        ? "selected"
                        : "" ?>>Search by Name</option>
                    <option value="venue" <?= $searchBy === "venue"
                        ? "selected"
                        : "" ?>>Search by Venue</option>
                </select>
                <button type="submit">Search</button>
                <?php if ($searchQuery): ?>
                    <button type="button" onclick="window.location.href='<?= strtok(
                        $_SERVER["REQUEST_URI"],
                        "?"
                    ) ?>'">Clear</button>
                <?php endif; ?>
            </form>
        </div>
        <!-- Actions -->
        <div class="actions">
            <button onclick="toggleAddForm()">Add New Event</button>
        </div>
        <!-- Add Event Form -->
        <div id="addEventForm" class="<?= $showAddForm
            ? ""
            : "hidden" ?> form-box">
            <h2 style="text-align: center; margin-top: 0;">Create New Event</h2>
            <form action="" method="POST">
                <input type="hidden" name="action" value="create">
                <label for="name">Event Name</label>
                <input type="text" id="name" name="name" required>
                <label for="date">Event Date</label>
                <input type="date" id="date" name="date" required min="<?= date(
                    "Y-m-d"
                ) ?>">
                <label for="time">Event Time</label>
                <input type="time" id="time" name="time" required>
                <label for="venue">Venue</label>
                <input type="text" id="venue" name="venue" required>
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required></textarea>
                <label for="status">Status</label>
                <select id="status" name="status" disabled>
                    <option value="Upcoming" selected>Upcoming</option>
                    <option value="Ongoing">Ongoing</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <div style="text-align: center;">
                    <button type="submit" class="submit-btn">Create Event</button>
                    <button type="button" class="main-btn cancel-btn" onclick="toggleAddForm()" style="margin-left: 10px;">Cancel</button>
                </div>
            </form>
        </div>
        <!-- Events Table -->
        <table class="event-table">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                    <th>Status</th>
                    <th>Staff Assigned</th>
                    <th>Feedback</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr id="event-row-<?= $event["event_id"] ?>">
                        <td>
                            <span class="event-name-link" onclick="toggleDetails(<?= $event[
                                "event_id"
                            ] ?>)">
                                <?= htmlspecialchars($event["event_name"]) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($event["event_date"]) ?></td>
                        <td><?= htmlspecialchars($event["event_time"]) ?></td>
                        <td><?= htmlspecialchars($event["event_venue"]) ?></td>
                        <td><?= htmlspecialchars($event["event_status"]) ?></td>
                        <td><?= $event["staff_count"] ?></td>
                        <td><?= $event["feedback_count"] ?></td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <button class="main-btn" onclick="toggleEditForm(<?= $event[
                                    "event_id"
                                ] ?>)">Edit</button>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="delete_id" value="<?= $event[
                                        "event_id"
                                    ] ?>">
                                    <button type="submit" class="main-btn danger-btn">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <!-- Edit Form Row -->
                    <tr id="edit-form-<?= $event["event_id"] ?>" class="hidden">
                        <td colspan="8">
                            <div class="form-box" style="margin: 0;">
                                <h2 style="text-align: center; margin-top: 0;">Edit Event</h2>
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="event_id" value="<?= $event[
                                        "event_id"
                                    ] ?>">
                                    <label for="edit_name">Event Name</label>
                                    <input type="text" id="edit_name" name="edit_name" 
                                           value="<?= htmlspecialchars(
                                               $event["event_name"]
                                           ) ?>" required>
                                    <label for="edit_date">Event Date</label>
                                    <input type="date" id="edit_date" name="edit_date" 
                                           value="<?= htmlspecialchars(
                                               $event["event_date"]
                                           ) ?>" required>
                                    <label for="edit_time">Event Time</label>
                                    <input type="time" id="edit_time" name="edit_time" 
                                           value="<?= htmlspecialchars(
                                               $event["event_time"]
                                           ) ?>" required>
                                    <label for="edit_venue">Venue</label>
                                    <input type="text" id="edit_venue" name="edit_venue" 
                                           value="<?= htmlspecialchars(
                                               $event["event_venue"]
                                           ) ?>" required>
                                    <label for="edit_description">Description</label>
                                    <textarea id="edit_description" name="edit_description" rows="4" required>
                                        <?= htmlspecialchars(
                                            $event["event_description"]
                                        ) ?>
                                    </textarea>
                                    <label for="edit_status">Status</label>
                                    <select id="edit_status" name="edit_status" required>
                                        <option value="Upcoming" <?= $event[
                                            "event_status"
                                        ] === "Upcoming"
                                            ? "selected"
                                            : "" ?>>Upcoming</option>
                                        <option value="Ongoing" <?= $event[
                                            "event_status"
                                        ] === "Ongoing"
                                            ? "selected"
                                            : "" ?>>Ongoing</option>
                                        <option value="Completed" <?= $event[
                                            "event_status"
                                        ] === "Completed"
                                            ? "selected"
                                            : "" ?>>Completed</option>
                                        <option value="Cancelled" <?= $event[
                                            "event_status"
                                        ] === "Cancelled"
                                            ? "selected"
                                            : "" ?>>Cancelled</option>
                                    </select>
                                    <div style="text-align: center;">
                                        <button type="submit" class="submit-btn">Update</button>
                                        <button type="button" class="main-btn cancel-btn" onclick="toggleEditForm(<?= $event[
                                            "event_id"
                                        ] ?>)" style="margin-left: 10px;">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <!-- Expandable Details Section -->
                    <tr id="details-<?= $event["event_id"] ?>" class="hidden">
                        <td colspan="8">
                            <div class="expandable">
                                <h3 style="margin-top: 0;">Manage Event</h3>
                                <!-- Staff Assignment -->
                                <div style="margin: 20px 0;">
                                    <h4>Assign Staff</h4>
                                    <?php
                                    // Get available employees
                                    $stmt = $conn->query(
                                        "SELECT user_id, user_name FROM users WHERE user_type = 'Employee'"
                                    );
                                    $employees = $stmt->fetchAll(
                                        PDO::FETCH_ASSOC
                                    );
                                    // Get staff already assigned to this event
                                    $stmt = $conn->prepare("SELECT u.user_id, u.user_name, u.user_email, p.participant_role 
                                    FROM participants p 
                                    JOIN users u ON p.user_id = u.user_id 
                                    WHERE p.event_id = ?");
                                    $stmt->execute([$event["event_id"]]);
                                    $assignedStaff = $stmt->fetchAll(
                                        PDO::FETCH_ASSOC
                                    );
                                    ?>
                                    <form method="POST" action="manage_event.php">
                                        <input type="hidden" name="event_id" value="<?= $event[
                                            "event_id"
                                        ] ?>">
                                        <div style="display: flex; gap: 15px; align-items: center;">
                                            <select name="user_id" class="assign-select" required>
                                                <option value="">-- Select Employee --</option>
                                                <option value="all">Select All Employees</option>
                                                <?php foreach (
                                                    $employees
                                                    as $emp
                                                ): ?>
                                                    <option value="<?= $emp[
                                                        "user_id"
                                                    ] ?>"><?= htmlspecialchars(
    $emp["user_name"]
) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select name="role" class="assign-select" required>
                                                <option value="">-- Select Role --</option>
                                                <option value="Organizer">Organizer</option>
                                                <option value="MC">MC</option>
                                                <option value="Speaker">Speaker</option>
                                                <option value="Attendee">Attendee</option>
                                            </select>
                                            <button type="submit" class="main-btn">Assign</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- Staff Assigned Table -->
<div style="margin: 20px 0;">
    <h4>Assigned Staff</h4>
    <?php if (!empty($assignedStaff)): ?>
        <table class="feedback-table">
        <thead>
    <tr>
        <th>Name</th>
        <th>Email</th> <!-- New Column -->
        <th>Role</th>
        <th>Action</th>
    </tr>
</thead>
            <tbody>
<!-- In the Assigned Staff Table -->
<?php foreach ($assignedStaff as $staff): ?>
    <tr>
        <td><?= htmlspecialchars($staff["user_name"]) ?></td>
        <td><?= htmlspecialchars($staff["user_email"]) ?></td>
        <td><?= htmlspecialchars($staff["participant_role"]) ?></td>
        <td>
            <form method="POST" onsubmit="return confirm('Are you sure you want to unassign this staff?');">
                <input type="hidden" name="action" value="unassign">
                <input type="hidden" name="user_id" value="<?= $staff[
                    "user_id"
                ] ?>">
                <input type="hidden" name="event_id" value="<?= $event[
                    "event_id"
                ] ?>">
                <!-- Add this line to fix the issue -->
                <input type="hidden" name="role" value="">
                <button type="submit" class="btn-delete">Unassign</button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No staff assigned yet.</p>
    <?php endif; ?>
</div>
                                <!-- Feedback Table -->
                                <div>
                                    <h4>Feedback</h4>
                                    <?php
                                    // Get feedback for this event
                                    $stmt = $conn->prepare("SELECT f.feedback_id, f.feedback_comment, 
                                                           f.feedback_rating, f.created_at, 
                                                           u.user_name
                                                           FROM feedback f
                                                           JOIN users u ON f.user_id = u.user_id
                                                           WHERE f.event_id = ?");
                                    $stmt->execute([$event["event_id"]]);
                                    $feedbacks = $stmt->fetchAll(
                                        PDO::FETCH_ASSOC
                                    );
                                    ?>
                                    <?php if (!empty($feedbacks)): ?>
                                        <table class="feedback-table">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Comment</th>
                                                    <th>Rating</th>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (
                                                    $feedbacks
                                                    as $feedback
                                                ): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars(
                                                            $feedback[
                                                                "user_name"
                                                            ]
                                                        ) ?></td>
                                                        <td><?= htmlspecialchars(
                                                            $feedback[
                                                                "feedback_comment"
                                                            ]
                                                        ) ?></td>
                                                        <td><?= htmlspecialchars(
                                                            $feedback[
                                                                "feedback_rating"
                                                            ]
                                                        ) ?>/5</td>
                                                        <td><?= htmlspecialchars(
                                                            $feedback[
                                                                "created_at"
                                                            ]
                                                        ) ?></td>
                                                        <td>
                                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                                                <input type="hidden" name="action" value="delete_feedback">
                                                                <input type="hidden" name="feedback_id" value="<?= $feedback[
                                                                    "feedback_id"
                                                                ] ?>">
                                                                <button type="submit" class="btn-delete">Delete</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p>No feedback yet.</p>
                                    <?php endif; ?>
                                </div>
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
            const form = document.getElementById('addEventForm');
            form.classList.toggle('hidden');
        }
        function toggleEditForm(eventId) {
            const row = document.getElementById('event-row-' + eventId);
            const formRow = document.getElementById('edit-form-' + eventId);
            row.style.display = row.style.display === 'none' ? '' : 'none';
            formRow.classList.toggle('hidden');
        }
        function toggleDetails(eventId) {
            const detailsRow = document.getElementById('details-' + eventId);
            detailsRow.classList.toggle('hidden');
        }

        // Restrict past time selection
        function updateMinTime() {
            const now = new Date();
            const minTime = now.toTimeString().slice(0, 5);
            document.getElementById('time').min = minTime;
        }

        updateMinTime();
        setInterval(updateMinTime, 1000);
    </script>
</body>
</html>