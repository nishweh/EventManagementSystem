<?php
session_start();

require 'db.php'; // Ensure this file defines $host, $dbname, $user, $pass

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Default message holder (used for showing alerts after redirect via query params)
$alertMessage = '';

if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
    $alertMessage = 'Account created successfully! Please sign in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';

    if (empty($email) || empty($password) || empty($type)) {
        echo "<script>alert('⚠️ Please fill in all required fields.'); window.history.back();</script>";
        exit;
    }

    // ✅ Use correct column names
    $stmt = $pdo->prepare("SELECT user_id, user_email, password_hash, user_type FROM users WHERE user_email = :email AND user_type = :type LIMIT 1");
    $stmt->execute(['email' => $email, 'type' => $type]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // ✅ Set session with correct column names
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['user_email'];
        $_SESSION['user_type'] = $user['user_type'];

        header("Location: profile.php");
        exit;
    } else {
        echo "<script>alert('❌ Invalid email, password, or role.'); window.history.back();</script>";
        exit;
    }
} else {
    // GET request: form will be rendered below
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>PIKATS - Login</title>
    <style>
      * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      body {
        background-color: #212f85; /* Approximation of original blue gradient */
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #fff;
        transition: background-color 0.8s ease-in-out;
      }

      .login-container {
        background: rgba(0, 0, 0, 0.3);
        padding: 30px 25px;
        border-radius: 12px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
        text-align: center;
        transform: scale(1);
        transition: all 0.8s ease-in-out;
      }

      .login-container h2 {
        margin-bottom: 25px;
        color: #c5cae9;
        font-weight: 700;
        font-size: 28px;
        transition: color 0.8s ease-in-out;
      }

      form input[type="email"],
      form input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        margin-bottom: 18px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        outline: none;
      }

      form input[type="email"]::placeholder,
      form input[type="password"]::placeholder {
        color: #666;
      }

      .type {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-bottom: 20px;
        color: #c5cae9;
        font-weight: 600;
        font-size: 16px;
        transition: color 0.8s ease-in-out;
      }

      .type label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: color 0.8s ease-in-out;
      }

      button {
        width: 100%;
        padding: 14px 0;
        background-color: #3949ab;
        border: none;
        border-radius: 8px;
        color: white;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.8s ease-in-out;
      }

      button:hover {
        background-color: #5c6bc0;
      }

      .back-link {
        display: block;
        text-align: center;
        margin-top: 40px;
        color: #90caf9;
        text-decoration: none;
        transition: color 0.8s ease-in-out;
      }

      .back-link:hover {
        text-decoration: underline;
      }

      /* Admin Mode Styles */
      body.admin-mode .login-container { transform: scale(0.98); }
      body.admin-mode .login-container h2,
      body.admin-mode .type,
      body.admin-mode .type label,
      body.admin-mode .back-link { color: #9e9e9e; }
      body.admin-mode button { background-color: #616161; }
      body.admin-mode button:hover { background-color: #757575; }
      body.admin-mode { background-color: #515151; }
    </style>
  </head>
  <body>
    <div class="login-container">
      <h2>Welcome to PIKATS</h2>
      <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />
        <div class="type">
          <label>
            <input type="radio" name="type" value="Admin" required /> Admin
          </label>
          <label>
            <input type="radio" name="type" value="Employee" /> Employee
          </label>
        </div>
        <button type="submit">Sign In</button>
      </form>
      <a href="index.html" class="back-link">← Back</a>
    </div>

    <script>
      // Function to update theme based on radio buttons
      const adminRadio    = document.querySelector('input[value="Admin"]');
      const employeeRadio = document.querySelector('input[value="Employee"]');
      const bodyElement   = document.body;

      function updateTheme() {
        if (adminRadio.checked) {
          bodyElement.classList.add('admin-mode');
        } else {
          bodyElement.classList.remove('admin-mode');
        }
      }

      // Initial theme update and event listeners
      updateTheme();
      adminRadio.addEventListener('change', updateTheme);
      employeeRadio.addEventListener('change', updateTheme);

      // Show alert if redirected from successful sign-up
      <?php if (!empty($alertMessage)): ?>
        alert('<?php echo addslashes($alertMessage); ?>');
      <?php endif; ?>
    </script>
  </body>
</html>