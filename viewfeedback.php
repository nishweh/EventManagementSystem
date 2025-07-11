<?php
require_once 'db.php';

try {
    // ✅ Use correct column names in JOINs and SELECT
    $stmt = $conn->query("
        SELECT 
            f.feedback_id,
            f.feedback_comment AS comment,
            f.feedback_rating AS rating,
            f.created_at,
            u.user_name AS username,
            e.event_name
        FROM feedback f
        JOIN users u ON f.user_id = u.user_id
        JOIN events e ON f.event_id = e.event_id
        ORDER BY f.created_at DESC
    ");
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching feedback: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>View Feedback</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #171f73 0%, #3f4ec1 100%);
      color: #e8eaf6;
      padding: 40px;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }

    .container {
      width: 100%;
      max-width: 900px;
      background: rgba(255, 255, 255, 0.08);
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: rgba(255, 255, 255, 0.05);
    }

    th, td {
      padding: 14px;
      border-bottom: 1px solid #c5cae9;
      color: #fff;
      text-align: center;
    }

    th {
      background-color: #3949ab;
    }

    tr:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .btn-delete {
      background: #e53935;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 5px;
      cursor: pointer;
    }

    .btn-delete:hover {
      background: #c62828;
    }

    a.back-link {
      display: inline-block;
      margin-top: 20px;
      color: #90caf9;
      text-decoration: none;
    }

    a.back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
  <div class="container">
    <h1>View Feedback</h1>

    <?php if (count($feedbacks) === 0): ?>
      <p>No feedback found.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Comment</th>
            <th>Event</th>
            <th>Rating</th>
            <th>Created At</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($feedbacks as $f): ?>
            <tr>
              <td><?= htmlspecialchars($f['username']) ?></td>
              <td><?= htmlspecialchars($f['comment']) ?></td>
              <td><?= htmlspecialchars($f['event_name']) ?></td>
              <td><?= htmlspecialchars($f['rating']) ?></td>
              <td><?= htmlspecialchars($f['created_at']) ?></td>
              <td>
                <form method="POST" action="delete_feedback.php" onsubmit="return confirm('Are you sure to delete this feedback?');">
                  <input type="hidden" name="delete_id" value="<?= htmlspecialchars($f['feedback_id']) ?>">
                  <button type="submit" class="btn-delete">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <a href="manage_event.php" class="back-link">← Back to Manage Events</a>
  </div>
</body>
</html>