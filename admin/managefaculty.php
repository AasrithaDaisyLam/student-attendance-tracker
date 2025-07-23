<?php
require_once '../conn.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$admin_id = $_SESSION['user_id'];
$error = "";

// Handle deletion if a delete request is made
if (isset($_GET['delete_faculty'])) {
  $faculty_id = intval($_GET['delete_faculty']);

  // Delete from faculty table
  $deleteFacultyStmt = $conn->prepare("DELETE FROM faculty WHERE id = ?");
  $deleteFacultyStmt->bind_param("i", $faculty_id);
  $deleteFacultyStmt->execute();
  $deleteFacultyStmt->close();

  header("Location: managefaculty.php");
  exit();
}

// Handle new faculty submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faculty'])) {
  $name = $_POST['faculty_name'];
  $email = $_POST['faculty_email'];
  $username = $_POST['faculty_username'];
  $password = password_hash($_POST['faculty_password'], PASSWORD_DEFAULT);

  if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
    $error = "Invalid name. Only letters and spaces allowed.";
  } elseif (!preg_match("/^[a-zA-Z0-9_]{4,20}$/", $username)) {
    $error = "Invalid username. Use 4–20 characters (letters, numbers, underscores only).";
  } else {
    $check_stmt = $conn->prepare("SELECT id FROM faculty WHERE email = ? OR username = ?");
    $check_stmt->bind_param("ss", $email, $username);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
      $error = "Email or username already exists.";
    } else {
      $stmt = $conn->prepare("INSERT INTO faculty (name, email, username, password, admin_id) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssi", $name, $email, $username, $password, $admin_id);
      $stmt->execute();
      $stmt->close();
      header("Location: managefaculty.php");
      exit();
    }
    $check_stmt->close();
  }
}

// Get faculty list
$faculty_stmt = $conn->prepare("SELECT id, name, email, username FROM faculty WHERE admin_id = ?");
$faculty_stmt->bind_param("i", $admin_id);
$faculty_stmt->execute();
$faculty_result = $faculty_stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_faculty_id'])) {
  $faculty_id = intval($_POST['reset_faculty_id']);
  $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

  $stmt = $conn->prepare("UPDATE faculty SET password = ? WHERE id = ?");
  $stmt->bind_param("si", $new_password, $faculty_id);
  $stmt->execute();
  $stmt->close();

  header("Location: managefaculty.php");
  exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Faculty</title>
  <link rel="stylesheet" href="newstyle.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .error-message {
      color: red;
      margin-bottom: 10px;
      font-weight: bold;
    }
    .action-buttons i {
      cursor: pointer;
      margin: 0 8px;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="sidebar" id="sidebar">
    <ul>
      <li onclick="location.href='dashboard.php'"><i class="fas fa-tachometer-alt"></i> Dashboard</li>
      <li onclick="location.href='managestudents.php'"><i class="fas fa-user-graduate"></i> Manage Students</li>
      <li onclick="location.href='assignclasses.php'"><i class="fas fa-chart-bar"></i> Assign Classes</li>
      <li onclick="location.href='managefaculty.php'"><i class="fas fa-chalkboard-teacher"></i><strong> Manage Faculty</strong></li>
      <li onclick="location.href='attendance_report.php'"><i class="fas fa-file-alt"></i> Reports</li> 
      <li id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</li>
    </ul>
  </div>

  <div id="logoutModal" class="modal">
    <div class="modal-content">
      <p>Are you sure you want to logout?</p>
      <div class="modal-actions">
        <button id="confirmLogout">OK</button>
        <button id="cancelLogout">Cancel</button>
      </div>
    </div>
  </div>

  <div class="main">
    <div class="topbar">
      <div class="left-topbar">
        <span class="menu-button" onclick="toggleSidebar()">☰</span>
        <span class="welcome-text">Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
      </div>
      <div class="right-icons">
        <span class="icon" onclick="showProfile()">👤</span>
      </div>
      <div id="profile-dropdown" class="dropdown hidden">
        <strong>Admin Details</strong>
        <p>Name: <?= htmlspecialchars($_SESSION['username']) ?></p>
        <p>Email: <?= htmlspecialchars($_SESSION['email']) ?></p>
      </div>
    </div>

    <!-- Add Faculty -->
    <div class="card form-card">
      <h4>Add Faculty</h4>
      <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="form-section">
        <input type="hidden" name="add_faculty" value="1">
        <div class="form-group">
          <label>Name</label>
          <input type="text" name="faculty_name" required pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="faculty_email" required>
        </div>
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="faculty_username" required pattern="^[a-zA-Z0-9_]{4,20}$" title="4-20 characters. Letters, numbers, and underscores only.">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="faculty_password" required>
        </div>
        <button type="submit" class="save-button">Add Faculty</button>
      </form>
    </div>

    <!-- Faculty List -->
    <div class="card">
      <h4>Faculty List</h4>
      <div class="form-group" style="margin-bottom: 15px;">
  <input type="text" id="facultySearch" class="form-control" placeholder="Search faculty by name..." onkeyup="filterFaculty()">
</div>

      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Username</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $faculty_result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td class="action-buttons">
                <a href="editfaculty.php?id=<?= $row['id'] ?>"><i class="fas fa-edit" title="Edit"></i></a>
                <i class="fas fa-key" title="Reset Password" onclick="showResetModal(<?= $row['id'] ?>)"></i>
                <i class="fas fa-trash" title="Delete" onclick="confirmDelete(<?= $row['id'] ?>)"></i>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <div id="resetModal" class="modal" style="display:none;">
  <div class="modal-content">
    <h4>Reset Password</h4>
    <form method="POST">
      <input type="hidden" id="reset_faculty_id" name="reset_faculty_id">
      <label>New Password:</label>
      <input type="password" name="new_password" required>
      <button type="submit">Update</button>
      <button type="button" onclick="closeResetModal()">Cancel</button>
    </form>
  </div>
</div>

    </div>
  </div>
</div>

<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
  }
  function showProfile() {
    toggleDropdown("profile-dropdown");
  }
  function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    if (dropdown) dropdown.classList.toggle("hidden");
  }
  window.addEventListener("click", function (e) {
    if (!e.target.closest(".icon")) {
      const dropdown = document.getElementById("profile-dropdown");
      if (dropdown) dropdown.classList.add("hidden");
    }
  });

  function confirmDelete(id) {
    if (confirm("Are you sure you want to delete this faculty? This action cannot be undone.")) {
      window.location.href = "managefaculty.php?delete_faculty=" + id;
    }
  }
  function filterFaculty() {
  const input = document.getElementById("facultySearch");
  const filter = input.value.toLowerCase();
  const table = document.querySelector("table");
  const rows = table.querySelectorAll("tbody tr");

  rows.forEach(row => {
    const nameCell = row.querySelector("td:first-child");
    const name = nameCell.textContent.toLowerCase();
    row.style.display = name.includes(filter) ? "" : "none";
  });
}

function showResetModal(id) {
  document.getElementById('reset_faculty_id').value = id;
  document.getElementById('resetModal').style.display = 'block';
}
function closeResetModal() {
  document.getElementById('resetModal').style.display = 'none';
}

</script>
<script src="app.js"></script>
</body>
</html>
