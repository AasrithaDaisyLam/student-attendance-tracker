<?php
session_start();
require_once '../conn.php';
$admin_id = $_SESSION['user_id'];
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $del_stmt = $conn->prepare("DELETE FROM students WHERE id = ? AND admin_id = ?");
    $del_stmt->bind_param("ii", $delete_id, $admin_id);
    $del_stmt->execute();
    header("Location: managestudents.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Students</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- ✅ Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-qFeoDyAJ0M1LNUtLxVgVjNUqkA+1mJH0BML6A0VnRXA7OmlTwlUeKxN0+1bLj+gD" crossorigin="anonymous">

  
  <link rel="stylesheet" href="newstyle.css">

  <!-- ✅ Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pDpwO12OEzKUThVBDG9YkBGBcD5/kqfZHRq/0JvvS46FuJm0kuTx9ApcoWvF5a1p" crossorigin="anonymous"></script>

</head>
<body>
<div class="container">
  <div class="sidebar" id="sidebar">
    <ul>
      <li onclick="location.href='dashboard.php'"><i class="fas fa-tachometer-alt"></i> Dashboard</li>
      <li onclick="location.href='managestudents.php'"><i class="fas fa-user-graduate"></i><strong> Manage Students</strong></li>
      <li onclick="location.href='assignclasses.php'"><i class="fas fa-chart-bar"></i> Assign Classes</li>
      <li onclick="location.href='managefaculty.php'"><i class="fas fa-chalkboard-teacher"></i> Manage Faculty</li>
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

    <!-- Add Student Form -->
    <div class="card form-card">
      <h4>Add Student</h4>
      <form method="POST" class="form-section" onsubmit="return validateStudentForm()">
        <div class="form-group">
          <label>Name</label>
          <input type="text" name="student_name" placeholder="Enter name" required />
        </div>
        <div class="form-group">
          <label>Roll No</label>
          <input type="text" name="roll_number" placeholder="Enter roll number" required />
        </div>
        <div class="form-group">
          <label>Class</label>
          <input type="text" name="class" placeholder="e.g., 1, 2, LKG" required />
        </div>
        <div class="form-group">
          <label>Section</label>
          <input type="text" name="section" placeholder="e.g., A, B" required />
        </div>
        <button type="submit" name="add_student" class="save-button">Add Student</button>
      </form>
    </div>

    <!-- Student Table -->
<div class="card">
  <h4>Student List</h4>

  <!-- 🔍 Search box -->
  <div class="form-group" style="margin-bottom: 15px;">
    <input type="text" id="studentSearch" class="form-control" placeholder="Search by name or roll number..." onkeyup="filterStudents()">
  </div>


    <div class="col-md-3">
      <select id="classFilter" class="form-select" onchange="filterStudents()">
        <option value="">All Classes</option>
        <?php
          $classResult = $conn->query("SELECT DISTINCT class FROM students WHERE admin_id = $admin_id ORDER BY class");
          while ($row = $classResult->fetch_assoc()) {
            echo "<option value='" . htmlspecialchars($row['class']) . "'>" . htmlspecialchars($row['class']) . "</option>";
          }
        ?>
      </select>
    </div>
    <div class="col-md-3">
      <select id="sectionFilter" class="form-select" onchange="filterStudents()">
        <option value="">All Sections</option>
        <?php
          $sectionResult = $conn->query("SELECT DISTINCT section FROM students WHERE admin_id = $admin_id ORDER BY section");
          while ($row = $sectionResult->fetch_assoc()) {
            echo "<option value='" . htmlspecialchars($row['section']) . "'>" . htmlspecialchars($row['section']) . "</option>";
          }
        ?>
      </select>
    </div>
  

  <!-- Student Table -->
  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>Name</th>
        <th>Roll No</th>
        <th>Class</th>
        <th>Section</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="studentTableBody">
      <?php
      $stmt = $conn->prepare("SELECT id,name, roll_number, class, section FROM students WHERE admin_id = ?");
      $stmt->bind_param("i", $admin_id);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['roll_number']) ?></td>
          <td><?= htmlspecialchars($row['class']) ?></td>
          <td><?= htmlspecialchars($row['section']) ?></td>
          <td>
            <a href="editstudent.php?id=<?= $row['id'] ?>"><i class="fas fa-edit" title="Edit"></i></a>
            <a href="managestudents.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')"><i class="fas fa-trash" title="Delete"></i></a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

  </div>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
  $name = trim($_POST['student_name']);
  $roll = trim($_POST['roll_number']);
  $class = trim($_POST['class']);
  $section = trim($_POST['section']);

  // Server-side validation
  if (!preg_match("/^[A-Za-z ]+$/", $name)) {
    die("Invalid name: only letters and spaces allowed.");
  }

  if (!preg_match("/^[A-Za-z0-9]+$/", $roll)) {
    die("Invalid roll number: only letters and digits allowed.");
  }

  if (!preg_match("/^[A-Za-z0-9 ]+$/", $class)) {
    die("Invalid class format.");
  }

  if (!preg_match("/^[A-Za-z]$/", $section)) {
    die("Invalid section: must be a single letter.");
  }

  $stmt = $conn->prepare("INSERT INTO students (name, roll_number, class, section, admin_id) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssi", $name, $roll, $class, $section, $admin_id);
  $stmt->execute();
  echo "<script>window.location.href='managestudents.php';</script>";
  exit();
}
?>

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
function filterStudents() {
  const input = document.getElementById("studentSearch");
  const filter = input.value.toLowerCase();
  const table = document.querySelector("table");
  const rows = table.querySelectorAll("tbody tr");

  rows.forEach(row => {
    const nameCell = row.querySelector("td:nth-child(1)");
    const rollCell = row.querySelector("td:nth-child(2)");
    const name = nameCell.textContent.toLowerCase();
    const roll = rollCell.textContent.toLowerCase();

    row.style.display = name.includes(filter) || roll.includes(filter) ? "" : "none";
  });
}

function filterStudents() {
  const input = document.getElementById("studentSearch").value.toLowerCase();
  const classFilter = document.getElementById("classFilter").value.toLowerCase();
  const sectionFilter = document.getElementById("sectionFilter").value.toLowerCase();

  const rows = document.querySelectorAll("#studentTableBody tr");

  rows.forEach(row => {
    const name = row.cells[0].textContent.toLowerCase();
    const roll = row.cells[1].textContent.toLowerCase();
    const studentClass = row.cells[2].textContent.toLowerCase();
    const section = row.cells[3].textContent.toLowerCase();

    const matchesSearch = name.includes(input) || roll.includes(input);
    const matchesClass = classFilter === "" || studentClass === classFilter;
    const matchesSection = sectionFilter === "" || section === sectionFilter;

    row.style.display = (matchesSearch && matchesClass && matchesSection) ? "" : "none";
  });
}

</script>
<script src="app.js"></script>
<script src="managestudents.js"></script>
</body>
</html>
