<?php
session_start();
require_once '../conn.php';

$error = '';  // For showing login error message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$role || !$username || !$password) {
        $error = "All fields are required.";
    } else {
        $table = $role === 'admin' ? 'admins' : 'faculty';

        $sql = "SELECT * FROM $table WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $role;
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            if ($role === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                $_SESSION['faculty_id'] = $user['id'];
                header("Location: ../faculty/dashboard.php");
            }
            exit();
        } else {
            $error = "Username or Password incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <link rel="stylesheet" href="facstyle.css">
  <style>
    body {
      background: linear-gradient(135deg, #5376d4, #8ea9f2);
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .login-box {
      background: white;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      max-width: 400px;
      width: 100%;
    }
    .login-box h2 {
      text-align: center;
      margin-bottom: 1rem;
    }
    .login-box input,
    .login-box select,
    .login-box button {
      width: 100%;
      padding: 10px;
      margin: 0.5rem 0;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    .login-box button {
      background-color: #5376d4;
      color: white;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .login-box button:hover {
      background-color: #3e5cb0;
    }
    .signup-link {
      text-align: center;
      margin-top: 10px;
    }
    .signup-link a {
      color: #5376d4;
      text-decoration: none;
    }
    .signup-link a:hover {
      text-decoration: underline;
    }
    .error-message {
      color: red;
      font-size: 0.9rem;
      text-align: center;
      margin-top: 0.5rem;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>Login</h2>
    <form method="POST" action="">
      <select name="role" required>
        <option value="">Select Role</option>
        <option value="admin" <?= isset($_POST['role']) && $_POST['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        <option value="faculty" <?= isset($_POST['role']) && $_POST['role'] === 'faculty' ? 'selected' : '' ?>>Faculty</option>
      </select>
      <input type="text" name="username" placeholder="Username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
      <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
    </form>
    <div class="signup-link">
      <p>Don’t have an Admin account? <a href="signup.php">Sign up</a></p>
    </div>
  </div>
</body>
</html>
