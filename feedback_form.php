<?php
session_start();
require 'db.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first.'); window.location.href = 'login.php';</script>";
    exit;
}

$userId = $_SESSION['user_id'];
$events = [];
$error = '';
$success = '';

try {
    // Fetch only events where the user has a valid participant status
    $stmt = $conn->prepare("
        SELECT e.event_id AS id, e.event_name AS name
        FROM events e
        JOIN participants p ON e.event_id = p.event_id
        WHERE p.user_id = :user_id
          AND p.participant_status IN ('Assigned', 'Accepted', 'Rejected', 'Present', 'Absent')
        ORDER BY e.event_date DESC
    ");
    $stmt->execute(['user_id' => $userId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching events: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Feedback</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="main.css">
  <style>
    .main-content {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px;
        box-sizing: border-box;
    }



    .container {
      width: 100%;
      max-width: 720px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.5);
    }

    h1 {
      font-size: 34px;
      font-weight: 800;
      color: #c5cae9;
      margin-bottom: 30px;
      text-align: center;
    }

    label {
      font-weight: 600;
      font-size: 15px;
      color: #bbdefb;
      display: block;
      margin-bottom: 6px;
    }

    select, textarea {
  width: 100%;
  padding: 14px 16px;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  background-color: rgba(255, 255, 255, 0.15);
  color: white;
  margin-bottom: 20px;
  transition: all 0.3s ease;
  box-sizing: border-box; /* Add this */
}


    select:focus, textarea:focus {
      background-color: rgba(255, 255, 255, 0.25);
      outline: none;
    }

    select option {
      color: #000;
    }

    textarea {
      resize: vertical;
      height: 140px;
    }

    .submit-btn {
      width: 100%;
      background: linear-gradient(145deg, #3949ab, #5c6bc0);
      border: none;
      padding: 14px 28px;
      font-weight: 700;
      font-size: 16px;
      color: #f0f4ff;
      border-radius: 14px;
      cursor: pointer;
      box-shadow: 0 6px 18px rgba(57, 73, 171, 0.7);
      transition: background 0.3s, transform 0.2s;
    }

    .submit-btn:hover {
      background: linear-gradient(145deg, #5c6bc0, #7986cb);
      transform: translateY(-2px);
    }

    a {
      display: block;
      margin-top: 25px;
      color: #90caf9;
      text-align: center;
      text-decoration: none;
      font-weight: 500;
    }

    a:hover {
      text-decoration: underline;
      color: #e3f2fd;
    }

    ::placeholder{
      color: white;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <main class="main-content">
    <div class="container">
      <h1>Send Us Feedback</h1>

      <form action="feedback.php" method="POST">
        <label for="event">Event</label>
        <select name="event" id="event" required>
          <option value="" disabled selected>-- Select Event --</option>
          <?php foreach ($events as $event): ?>
            <option value="<?= htmlspecialchars($event['id']) ?>">
              <?= htmlspecialchars($event['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="rating">Rating</label>
        <select name="rating" id="rating" required>
          <option value="" disabled selected>-- Rate the Event --</option>
          <option value="1">1 ⭐</option>
          <option value="2">2 ⭐⭐</option>
          <option value="3">3 ⭐⭐⭐</option>
          <option value="4">4 ⭐⭐⭐⭐</option>
          <option value="5">5 ⭐⭐⭐⭐⭐</option>
        </select>

        <label for="comment">Comment</label>
        <textarea id="comment" name="comment" placeholder="Write your feedback..." required></textarea>

        <button type="submit" class="submit-btn">Submit Feedback</button>
      </form>

      <a href="profile.php">← Back to Dashboard</a>
    </div>
  </main>
</body>
</html>