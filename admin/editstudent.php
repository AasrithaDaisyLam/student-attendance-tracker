<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Step 1: Validate student ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid student ID.");
}

$student_id = intval($_GET['id']);

// Step 2: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['student_name']);
    $roll_number = trim($_POST['roll_number']);
    $class = trim($_POST['class']);
    $section = trim($_POST['section']);

    if ($name && $roll_number && $class && $section) {
        $update = $conn->prepare("UPDATE students SET name = ?, roll_number = ?, class = ?, section = ? WHERE id = ? AND admin_id = ?");
        $update->bind_param("ssssii", $name, $roll_number, $class, $section, $student_id, $admin_id);
        $update->execute();
        $update->close();
        header("Location: managestudents.php");
        exit();
    } else {
        $error = "All fields are required.";
    }
}

// Step 3: Fetch student data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND admin_id = ?");
$stmt->bind_param("ii", $student_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found or unauthorized access.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Student</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <h3>Edit Student Info</h3>
    
    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ... same PHP part as before ... -->

<form method="POST" class="card p-4 shadow-sm bg-white" onsubmit="return validateStudentForm()">
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="student_name" value="<?= htmlspecialchars($student['name']) ?>" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Roll Number</label>
    <input type="text" name="roll_number" value="<?= htmlspecialchars($student['roll_number']) ?>" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Class</label>
    <input type="text" name="class" value="<?= htmlspecialchars($student['class']) ?>" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Section</label>
    <input type="text" name="section" value="<?= htmlspecialchars($student['section']) ?>" class="form-control" required>
  </div>

  <div class="d-flex justify-content-between">
    <a href="managestudents.php" class="btn btn-secondary">Cancel</a>
    <button type="submit" class="btn btn-success">Update</button>
  </div>
</form>

  </div>

  <script src="managestudents.js"></script>

</body>
</html>
