<?php
session_start();
require_once 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Accept/Decline/Present/Absent actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['event_id'], $_POST['action'])) {
        $event_id = (int)$_POST['event_id'];
        $action = $_POST['action'];

        // Map action to status
        $status = null;

        switch ($action) {
            case 'accept':
                $status = 'Accepted';
                break;
            case 'decline':
                $status = 'Rejected';
                break;
            case 'present':
                $status = 'Present';
                break;
            case 'absent':
                $status = 'Absent';
                break;
            default:
                $status = null;
        }

        if ($status === null) {
            die("Invalid action.");
        }

        try {
            $stmt = $conn->prepare("UPDATE participants SET participant_status = ? WHERE event_id = ? AND user_id = ?");
            $stmt->execute([$status, $event_id, $user_id]);
            header("Location: view.php");
            exit;
        } catch (PDOException $e) {
            echo "Error updating status: " . $e->getMessage();
            exit;
        }
    }
}

// Fetch assigned events
try {
    $stmt = $conn->prepare("
        SELECT 
            e.event_id AS id, 
            e.event_name AS name, 
            e.event_date AS date, 
            e.event_time AS time, 
            e.event_venue AS venue, 
            e.event_description AS description,
            e.event_status AS event_status,
            p.participant_status,
            p.participant_role
        FROM events e
        JOIN participants p ON e.event_id = p.event_id
        WHERE p.user_id = ?
        ORDER BY e.event_date DESC
    ");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching events: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Assigned Events</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="main.css">
  <style>
    body {
      margin: 0;
      padding: 40px 20px;
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #171f73 0%, #3f4ec1 100%);
      color: #e8eaf6;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      box-sizing: border-box;
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
      font-size: 32px;
      font-weight: 800;
      color: #c5cae9;
      margin-bottom: 30px;
      text-align: center;
    }
    .card {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }
    .card h2 {
      margin-top: 0;
      font-size: 20px;
      font-weight: 600;
      color: #c5cae9;
    }
    .card p {
      margin: 6px 0;
      color: #e8eaf6;
    }
    .actions {
      margin-top: 10px;
    }
    .btn-accept,
    .btn-decline,
    .btn-present,
    .btn-absent {
      background: linear-gradient(145deg, #388e3c, #43a047);
      color: #fff;
      border: none;
      padding: 8px 14px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      box-shadow: 0 4px 10px rgba(56, 142, 60, 0.5);
      transition: all 0.2s ease-in-out;
      margin-right: 10px;
    }
    .btn-accept:hover,
    .btn-present:hover {
      background: linear-gradient(145deg, #2e7d32, #388e3c);
      transform: translateY(-2px);
    }
    .btn-decline,
    .btn-absent {
      background: linear-gradient(145deg, #d32f2f, #e53935);
      box-shadow: 0 4px 10px rgba(211, 47, 47, 0.5);
    }
    .btn-decline:hover,
    .btn-absent:hover {
      background: linear-gradient(145deg, #b71c1c, #d32f2f);
      transform: translateY(-2px);
    }
    .status {
      font-weight: 600;
      color: #ffcc80;
    }
    a.back-link {
      display: inline-block;
      margin-top: 20px;
      color: #90caf9;
      text-decoration: none;
      font-weight: 600;
    }
    a.back-link:hover {
      text-decoration: underline;
    }
    .no-events {
      color: #f0f4ff;
      text-align: center;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <main class="main-content">
    <div class="container">
      <h1>Assigned Events</h1>
      <?php if (empty($events)): ?>
        <p class="no-events">No events assigned yet.</p>
      <?php else: ?>
        <?php foreach ($events as $event): ?>
          <div class="card">
            <h2><?= htmlspecialchars($event['name']) ?></h2>
            <p><strong>Date:</strong> <?= htmlspecialchars($event['date']) ?></p>
            <p><strong>Time:</strong> <?= htmlspecialchars($event['time']) ?></p>
            <p><strong>Venue:</strong> <?= htmlspecialchars($event['venue']) ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($event['description']) ?></p>
            <p><strong>Role:</strong> <?= htmlspecialchars($event['participant_role']) ?></p>
            <p><strong>Event Status:</strong> <?= htmlspecialchars($event['event_status']) ?></p>

            <div class="actions">
              <?php if ($event['participant_status'] === 'Assigned'): ?>
                <form method="POST" action="" style="display:inline;">
                  <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                  <input type="hidden" name="action" value="accept">
                  <button type="submit" class="btn-accept">Accept</button>
                </form>
                <form method="POST" action="" style="display:inline;">
                  <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                  <input type="hidden" name="action" value="decline">
                  <button type="submit" class="btn-decline">Decline</button>
                </form>
              <?php elseif ($event['event_status'] === 'Ongoing' && $event['participant_status'] === 'Accepted'): ?>
                <form method="POST" action="" style="display:inline;">
                  <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                  <input type="hidden" name="action" value="present">
                  <button type="submit" class="btn-present">Present</button>
                </form>
                <form method="POST" action="" style="display:inline;">
                  <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                  <input type="hidden" name="action" value="absent">
                  <button type="submit" class="btn-absent">Absent</button>
                </form>
              <?php else: ?>
                <p class="status">Your Response: <?= htmlspecialchars($event['participant_status']) ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <a href="profile.php" class="back-link">← Back to Dashboard</a>
    </div>
  </main>
</body>
</html>