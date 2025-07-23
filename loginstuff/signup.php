<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../conn.php'; // adjust path if needed

    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        echo "<script>alert('Signup successful. Please login.'); window.location.href='login.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Signup</title>
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
  </style>
</head>
<body>
  <div class="login-box">
    <h2>Admin Signup</h2>
    <form method="POST" onsubmit="return validateForm()">
  <input type="text" name="username" placeholder="Username" required pattern="[A-Za-z ]+" title="Only alphabets and spaces allowed.">
  
  <input type="email" name="email" placeholder="Email" required>
  
  <input type="password" name="password" placeholder="Password" required minlength="6" title="Minimum 6 characters with at least one special character.">
  
  <button type="submit">Sign Up</button>
</form>

  </div>

<script>
function validateForm() {
  const password = document.querySelector('input[name="password"]').value;
  
  // Check for at least one special character
  const specialCharPattern = /[!@#$%^&*(),.?":{}|<>]/;

  if (!specialCharPattern.test(password)) {
    alert("Password must contain at least one special character.");
    return false;
  }

  return true;
}
</script>


</body>
</html>
