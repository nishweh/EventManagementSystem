<?php
session_start();

// Include database credentials
include("db.php");

// Establish a fresh PDO connection (db.php only defines variables)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Variable to hold feedback messages for the user
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = isset($_POST['name'])            ? trim($_POST['name'])      : '';
    $email            = isset($_POST['email'])           ? trim($_POST['email'])     : '';
    $password         = isset($_POST['password'])        ? $_POST['password']        : '';
    $confirm_password = isset($_POST['confirm_password'])? $_POST['confirm_password']: '';
    $type             = 'Employee'; // Default account type

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format.';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } else {
        try {
            // Check for existing email
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_email = :email");
            $stmt->execute(['email' => $email]);

            if ($stmt->fetchColumn() > 0) {
                $message = 'Email is already registered.';
            } else {
                // Insert new user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (user_name, user_email, password_hash, user_type) VALUES (:name, :email, :password_hash, :type)");
                $insert->execute([
                    'name'          => $name,
                    'email'         => $email,
                    'password_hash' => $password_hash,
                    'type'          => $type
                ]);

                // Show success alert then redirect to login page
                echo "<script>alert('Registration successful!'); window.location.href='login.php?signup=success';</script>";
                exit;
            }
        } catch (PDOException $e) {
            $message = 'Error during registration: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sign Up</title>
    <style>
      body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(to right, #1a237e, #283593);
        color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
      }

      .signup-container {
        background: rgba(0, 0, 0, 0.25);
        padding: 40px 30px;
        border-radius: 12px;
        width: 100%;
        max-width: 400px;
        box-sizing: border-box;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.6);
      }

      h2 {
        text-align: center;
        margin-bottom: 30px;
        font-size: 28px;
        color: #fff;
        font-weight: 700;
      }

      form input[type="text"],
      form input[type="email"],
      form input[type="password"],
      form select {
        width: 100%;
        padding: 12px 15px;
        margin-bottom: 20px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        box-sizing: border-box;
      }

      form input[type="text"]:focus,
      form input[type="email"]:focus,
      form input[type="password"]:focus,
      form select:focus {
        outline: none;
        box-shadow: 0 0 6px #5c6bc0;
      }

      button {
        width: 100%;
        padding: 14px 0;
        font-size: 18px;
        border: none;
        border-radius: 8px;
        background-color: #3949ab;
        color: white;
        cursor: pointer;
        transition: background-color 0.3s ease;
        font-weight: 600;
      }

      button:hover {
        background-color: #5c6bc0;
      }

      .login-link {
        margin-top: 15px;
        text-align: center;
        font-size: 14px;
      }

      .login-link a {
        color: #9fa8da;
        text-decoration: none;
      }

      .login-link a:hover {
        text-decoration: underline;
      }

      .back-link {
        display: block;
        text-align: center;
        margin-top: 40px;
        color: #90caf9;
        text-decoration: none;
      }

      .back-link:hover {
        text-decoration: underline;
      }

      .message {
        text-align: center;
        margin-bottom: 20px;
        font-weight: bold;
        color: #ffcccb;
      }
    </style>
  </head>
  <body>
    <div class="signup-container">
      <h2>Create Your Account</h2>
      <?php if (!empty($message)) : ?>
        <div class="message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
        <input type="text" name="name" placeholder="Full Name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required />
        <input type="email" name="email" placeholder="Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm Password" required />
        <button type="submit">Sign Up</button>
      </form>

      <div class="login-link">
        Already have an account? <a href="login.php">Log In</a>
      </div>
      <a href="index.html" class="back-link">← Back</a>
    </div>
  </body>
</html>