<?php
if (!isset($_SESSION)) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Reuse PDO connection or establish new one
if (isset($conn) && $conn instanceof PDO) {
    $sidebar_pdo = $conn;
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $sidebar_pdo = $pdo;
} else {
    include_once 'db.php';
    if (isset($conn) && $conn instanceof PDO) {
        $sidebar_pdo = $conn;
    }
}

if (isset($sidebar_pdo) && $sidebar_pdo instanceof PDO) {
    try {
        $stmt = $sidebar_pdo->prepare("SELECT user_name, user_email, user_type FROM users WHERE user_id = :id");
        $stmt->execute(array('id' => $_SESSION['user_id']));
        $sidebar_user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $sidebar_user = array();
    }
}

if (!isset($sidebar_user) || !$sidebar_user) {
    $sidebar_user = array(
        'user_name' => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User',
        'user_email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '',
        'user_type' => isset($_SESSION['user_type']) ? $_SESSION['user_type'] : ''
    );
}
?>
<style>
body.with-sidebar {
  margin-left: 0 !important;
  padding-left: 280px !important;
  display: block !important;
  overflow-x: hidden;
}
body.admin-theme {
  background: linear-gradient(135deg, #2c2c2c 0%, #1b1b1b 100%) !important;
  color: #eeeeee !important;
}
body.user-theme {
  background: linear-gradient(135deg, #171f73 0%, #3f4ec1 100%) !important;
  color: #e8eaf6 !important;
}
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 280px;
  height: 100vh;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(12px);
  box-shadow: 2px 0 15px rgba(0, 0, 0, 0.6);
  display: flex;
  flex-direction: column;
  padding: 30px 25px;
  overflow-y: auto;
  z-index: 1000;
  flex-shrink: 0;
}
.sidebar-logo {
  height: 35px;
  margin-right: 12px;
  vertical-align: middle;
}
.sidebar-header {
  display: flex;
  align-items: center;
  font-weight: 900;
  font-size: 28px;
  color: #c5cae9;
  letter-spacing: 0.1em;
  margin-bottom: 40px;
  user-select: none;
}
body.admin-theme .sidebar-header { color: #cccccc; }
.profile-small {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 18px;
  padding: 20px 18px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35);
  margin-bottom: 50px;
}
.profile-small label {
  font-weight: 700;
  font-size: 13px;
  color: #9fa8da;
  margin: 0;
  padding: 0;
  letter-spacing: 0.04em;
}
body.admin-theme .profile-small label { color: #aaaaaa; }
.profile-small p {
  margin: 6px 0 0 0;
  font-size: 17px;
  font-weight: 600;
  color: #f0f4ff;
  margin-bottom: 14px;
}
body.admin-theme .profile-small p { color: #eeeeee; }
.nav-button {
  background: linear-gradient(145deg, #3949ab, #5c6bc0);
  border: none;
  border-radius: 16px;
  padding: 18px 0;
  font-weight: 700;
  font-size: 17px;
  color: #f0f4ff;
  cursor: pointer;
  margin-bottom: 18px;
  box-shadow: 0 6px 20px rgba(57, 73, 171, 0.7);
  transition: background-color 0.3s ease, box-shadow 0.3s ease, transform 0.15s ease;
  user-select: none;
  width: 100%;
}
body.admin-theme .nav-button {
  background: linear-gradient(145deg, #555555, #777777);
  box-shadow: 0 6px 20px rgba(80, 80, 80, 0.7);
  color: #f0f0f0;
}
.nav-button:hover {
  background: linear-gradient(145deg, #5c6bc0, #7986cb);
  box-shadow: 0 10px 30px rgba(92, 107, 192, 0.85);
  transform: translateY(-3px);
}
body.admin-theme .nav-button:hover {
  background: linear-gradient(145deg, #777777, #999999);
  box-shadow: 0 10px 30px rgba(100, 100, 100, 0.85);
}
.nav-button:active {
  transform: translateY(0);
  box-shadow: 0 6px 20px rgba(57, 73, 171, 0.7);
}
.logout-container {
  position: fixed;
  top: 20px;
  right: 30px;
  z-index: 1000;
}
.logout-button {
  background: #c62828;
  box-shadow: 0 6px 20px rgba(198, 40, 40, 0.85);
  border: none;
  border-radius: 16px;
  padding: 14px 24px;
  font-weight: 700;
  font-size: 17px;
  color: #f0f4ff;
  cursor: pointer;
  user-select: none;
  transition: background-color 0.3s ease, box-shadow 0.3s ease, transform 0.15s ease;
}
.logout-button:hover {
  background: #ef5350;
  box-shadow: 0 10px 30px rgba(239, 83, 80, 0.85);
  transform: translateY(-2px);
}
@media (max-width: 720px) {
  body.with-sidebar {
    margin-left: 0;
  }
  .sidebar {
    position: static;
    width: 100%;
    flex-direction: row;
    max-height: none;
  }
  .nav-button {
    flex: 1 0 120px;
    margin: 0 8px;
    padding: 14px 0;
    font-size: 14px;
  }
  .logout-container {
    position: static;
    margin: 10px auto 0 auto;
    text-align: center;
  }
}
a:hover {
  text-decoration: none;
}
.nav-button.active {
    background: linear-gradient(145deg, #7986cb, #9fa8da); /* brighter gradient */
    color: #ffffff;
    font-weight: 900;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.4), 0 4px 12px rgba(0, 0, 0, 0.5);
    transform: scale(1.03);
    border: 2px solid #c5cae9;
    pointer-events: none;
}

</style>
<aside class="sidebar">
  <div class="sidebar-header">
    <img src="img/logopikats.png" alt="PIKATS Logo" class="sidebar-logo">
    PIKATS
  </div>
  <div class="profile-small">
    <label>Name:</label>
    <p><?php echo htmlspecialchars($sidebar_user['user_name']); ?></p>
    <label>Email:</label>
    <p><?php echo htmlspecialchars($sidebar_user['user_email']); ?></p>
    <label>Role:</label>
    <p><?php echo htmlspecialchars($sidebar_user['user_type']); ?></p>
  </div>

  <button class="nav-button <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>" onclick="location.href='profile.php'">Dashboard</button>

  <?php if ($sidebar_user['user_type'] === 'Admin') { ?>
      <button class="nav-button <?php echo ($currentPage == 'userprofile.php') ? 'active' : ''; ?>" onclick="location.href='userprofile.php'">User Profile</button>
      <button class="nav-button <?php echo ($currentPage == 'manage_user.php') ? 'active' : ''; ?>" onclick="location.href='manage_user.php'">Manage Accounts</button>
      <button class="nav-button <?php echo ($currentPage == 'manage_event.php') ? 'active' : ''; ?>" onclick="location.href='manage_event.php'">Manage Events</button>
      <button class="nav-button <?php echo ($currentPage == 'chart.php') ? 'active' : ''; ?>" onclick="location.href='chart.php'">Ongoing Events</button>
  <?php } elseif ($sidebar_user['user_type'] === 'Employee') { ?>
      <button class="nav-button <?php echo ($currentPage == 'userprofile.php') ? 'active' : ''; ?>" onclick="location.href='userprofile.php'">User Profile</button>
      <button class="nav-button <?php echo ($currentPage == 'view.php') ? 'active' : ''; ?>" onclick="location.href='view.php'">View Events</button>
      <button class="nav-button <?php echo ($currentPage == 'feedback_form.php') ? 'active' : ''; ?>" onclick="location.href='feedback_form.php'">Submit Feedback</button>
  <?php } ?>
</aside>
<div class="logout-container">
  <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var userType = <?php echo json_encode($sidebar_user['user_type']); ?>;
    document.body.classList.add('with-sidebar');
    if (userType === 'Admin') {
        document.body.classList.add('admin-theme');
    } else {
        document.body.classList.add('user-theme');
    }
});
</script>
