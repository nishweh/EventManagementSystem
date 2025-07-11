<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT user_name, user_email, user_type FROM users WHERE user_id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['user_type'] = $user['user_type'];

    $stmt = $pdo->prepare("
        SELECT 
            e.event_id, 
            e.event_name, 
            p.participant_role, 
            p.participant_status, 
            e.event_status
        FROM participants p
        JOIN events e ON p.event_id = e.event_id
        WHERE p.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard</title>
  <link rel="stylesheet" href="main.css">
  <style>
    body {
      background: #1a237e;
      color: white;
      transition: background 0.3s ease;
    }

    .main-content header {
      font-size: 36px;
      font-weight: 900;
      margin-bottom: 20px;
      color: #c5cae9;
    }

    .main-content h2 {
      font-weight: 600;
      font-size: 24px;
      color: #e8eaf6;
    }

    #notifications div {
      margin-top: 20px;
      padding: 15px;
      background-color: rgba(255, 255, 255, 0.05);
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }

    #notifications p {
      margin: 10px 0;
      font-size: 16px;
      color: #e8eaf6;
    }

    #notifications strong {
      color: #ffcc80;
    }

    ::-webkit-scrollbar {
      width: 10px;
    }

    ::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.05);
    }

    ::-webkit-scrollbar-thumb {
      background: linear-gradient(145deg, #3949ab, #5c6bc0);
      border-radius: 10px;
    }

    a:hover {
      text-decoration: none;
    }

    /* Admin: GREY theme override */
    body.admin-theme {
      background-color: #2c2c2c;
      color: #e0e0e0;
    }

    body.admin-theme .main-content header,
    body.admin-theme .main-content h2,
    body.admin-theme #notifications p {
      color: #e0e0e0;
    }

    body.admin-theme #notifications div {
      background-color: rgba(255, 255, 255, 0.06);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.6);
    }

    body.admin-theme #notifications strong {
      color: #ffffff;
    }

    body.admin-theme ::-webkit-scrollbar-thumb {
      background: #666;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <main class="main-content">
    <header>Welcome, <?= htmlspecialchars($user['user_name']) ?>!</header>
    <h2>My Dashboard</h2>
    <hr>
    <h2>Messages</h2>
    <div id="notifications"></div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const userType = <?= json_encode($_SESSION['user_type']) ?>;
      if (userType === 'Admin') {
        document.body.classList.add('admin-theme');
      }

      const events = <?= json_encode($events) ?>;
      const container = document.getElementById('notifications');

      if (events.length === 0) {
        container.innerHTML = '<p><div>No new messages.</div></p>';
        return;
      }

      events.forEach(event => {
        container.innerHTML += `
          <p><div>You have been assigned as <strong>${event.participant_role}</strong> on <strong>${event.event_name}</strong>.</div></p>
          <p><div>Your status for <strong>${event.event_name}</strong> is <strong>${event.participant_status}</strong>.</div></p>
        `;

        if (event.event_status === 'Upcoming') {
          container.innerHTML += `<p><div><strong>${event.event_name}</strong> details have changed. Click <a href="view.php" style="color:#ffcc80;text-decoration:underline;"><strong>View Events</strong></a> for updated details.</div></p>`;
        }

        if (event.event_status === 'Ongoing' && event.participant_status === 'Accepted') {
          container.innerHTML += `<p><div><strong>${event.event_name}</strong> is Ongoing. <a href="view.php" style="color:#ffcc80;text-decoration:underline;"><strong>View Events</strong></a> for Attendance.</div></p>`;
        }
      });
    });
  </script>
</body>
</html>
