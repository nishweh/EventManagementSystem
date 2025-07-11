<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include("db.php");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT user_name, user_email, user_type, created_at FROM users WHERE user_id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['user_type'] = $user['user_type'];

    if (!$user) {
        die("User not found.");
    }

} catch (PDOException $e) {
    die("DB error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Profile</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />
  <style>
    body {
      margin: 0;
      padding: 40px;
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #171f73 0%, #3f4ec1 100%);
      color: #e8eaf6;
      min-height: 100vh;
      background-repeat: no-repeat;
      background-attachment: fixed;
      background-size: cover;
      transition: background 0.3s ease;
    }

    h1 {
      font-size: 32px;
      font-weight: 800;
      color: #c5cae9;
      margin-bottom: 10px;
      text-align: center;
    }

    .profile-box {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 30px;
      padding-top: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
      max-width: 600px;
      margin: auto;
    }

    label {
      font-weight: 600;
      font-size: 13px;
      color: #9fa8da;
      margin-top: 20px;
      display: block;
    }

    p {
      margin: 5px 0 15px;
      font-size: 18px;
      font-weight: 600;
    }

    a {
      color: #90caf9;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    /* GREY THEME FOR ADMINS */
    body.admin-theme {
      background: #2c2c2c !important;
      color: #e0e0e0 !important;
    }

    body.admin-theme h1 {
      color: #f0f0f0 !important;
    }

    body.admin-theme .profile-box {
      background: rgba(255, 255, 255, 0.07);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.6);
    }

    body.admin-theme label {
      color: #b0b0b0;
    }

    body.admin-theme a {
      color: #cccccc;
    }

    body.admin-theme a:hover {
      color: white;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <h1>User Profile</h1>

  <div class="profile-box">
    <label>Name:</label>
    <p><?= htmlspecialchars($user['user_name']) ?></p>

    <label>Email:</label>
    <p><?= htmlspecialchars($user['user_email']) ?></p>

    <label>Role:</label>
    <p><?= htmlspecialchars($user['user_type']) ?></p>

    <label>Account Created:</label>
    <p><?= date('F j, Y', strtotime($user['created_at'])) ?></p>

    <a href="profile.php">← Back to Dashboard</a>
  </div>

  <script>
    const userType = <?= json_encode($_SESSION['user_type']) ?>;
    if (userType === 'Admin') {
      document.body.classList.add('admin-theme');
    }
  </script>
</body>
</html>
