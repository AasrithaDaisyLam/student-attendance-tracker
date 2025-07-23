<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Step 1: Validate faculty ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid faculty ID.");
}

$faculty_id = intval($_GET['id']);

// Step 2: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['faculty_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    if ($name && $username && $email) {
        // Check for unique email and username
        $check = $conn->prepare("SELECT id FROM faculty WHERE (email = ? OR username = ?) AND id != ? AND admin_id = ?");
        $check->bind_param("ssii", $email, $username, $faculty_id, $admin_id);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Email or username already in use by another faculty.";
        } else {
            $update = $conn->prepare("UPDATE faculty SET name = ?, username = ?, email = ? WHERE id = ? AND admin_id = ?");
            $update->bind_param("sssii", $name, $username, $email, $faculty_id, $admin_id);
            $update->execute();
            $update->close();
            header("Location: managefaculty.php");
            exit();
        }

        $check->close();
    } else {
        $error = "All fields are required.";
    }
}

// Step 3: Fetch faculty data
$stmt = $conn->prepare("SELECT * FROM faculty WHERE id = ? AND admin_id = ?");
$stmt->bind_param("ii", $faculty_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();
$stmt->close();

if (!$faculty) {
    die("Faculty not found or unauthorized access.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Faculty</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <h3>Edit Faculty Info</h3>
    
    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm bg-white">
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="faculty_name" required pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" value="<?= htmlspecialchars($faculty['name']) ?>" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" required pattern="^[a-zA-Z0-9_]{4,20}$" title="4-20 characters. Letters, numbers, and underscores only." value="<?= htmlspecialchars($faculty['username']) ?>" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($faculty['email']) ?>" class="form-control" required>
      </div>

      <div class="d-flex justify-content-between">
        <a href="managefaculty.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-success">Update</button>
      </div>
    </form>
  </div>
</body>
</html>
