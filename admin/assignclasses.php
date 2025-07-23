<?php
require_once '../conn.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$admin_id = $_SESSION['user_id'];

// Handle deletion
if (isset($_GET['delete_assignment'])) {
  $delete_id = $_GET['delete_assignment'];

  $del_stmt = $conn->prepare("
    DELETE fs FROM faculty_subjects fs
    JOIN faculty f ON fs.faculty_id = f.id
    WHERE fs.id = ? AND f.admin_id = ?
  ");
  $del_stmt->bind_param("ii", $delete_id, $admin_id);
  $del_stmt->execute();
  header("Location: assignclasses.php");
  exit();
}


// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_class'])) {
  $faculty_id = $_POST['faculty_id'];
  $class = $_POST['class'];
  $section = $_POST['section'];
  $subject_id = $_POST['subject_id'];
  $day = $_POST['day_of_week'];
  $start_time = $_POST['start_time'];
  $end_time = $_POST['end_time'];

  $check = $conn->prepare("
    SELECT id FROM faculty_subjects
    WHERE faculty_id = ?
      AND day_of_week = ?
      AND (
        (? < end_time AND ? > start_time)
      )
  ");
  $check->bind_param("isss", $faculty_id, $day, $start_time, $end_time);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    $_SESSION['assign_error'] = "This faculty is already assigned to a class during this time.";
    header("Location: assignclasses.php");
    exit();
  }
  $check->close();

  $stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_id, class, section, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("iisssss", $faculty_id, $subject_id, $class, $section, $day, $start_time, $end_time);
  $stmt->execute();
  $stmt->close();
  header("Location: assignclasses.php");
  exit();
}

// Get faculty and subject lists
$faculty_stmt = $conn->prepare("SELECT id, name FROM faculty WHERE admin_id = ?");
$faculty_stmt->bind_param("i", $admin_id);
$faculty_stmt->execute();
$faculty_list = $faculty_stmt->get_result();

$subject_stmt = $conn->prepare("SELECT id, name FROM subjects WHERE is_global = 1 OR admin_id = ?");
$subject_stmt->bind_param("i", $admin_id);
$subject_stmt->execute();
$subject_list = $subject_stmt->get_result();

// Fetch current assignments (joined with faculty and subject names)
$assignment_stmt = $conn->prepare("
  SELECT fs.id, f.name AS faculty_name, s.name AS subject_name, fs.class, fs.section, fs.day_of_week, fs.start_time, fs.end_time
  FROM faculty_subjects fs
  JOIN faculty f ON fs.faculty_id = f.id
  JOIN subjects s ON fs.subject_id = s.id
  WHERE f.admin_id = ?
  ORDER BY fs.day_of_week, fs.start_time
");
$assignment_stmt->bind_param("i", $admin_id);
$assignment_stmt->execute();
$assignments = $assignment_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Assign Classes</title>
  <link rel="stylesheet" href="newstyle.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .hidden {
      display: none !important;
    }
    .subject-form-flex {
      display: flex;
      gap: 10px;
      margin-top: 10px;
      align-items: center;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="sidebar" id="sidebar">
    <ul>
      <li onclick="location.href='dashboard.php'"><i class="fas fa-tachometer-alt"></i> Dashboard</li>
      <li onclick="location.href='managestudents.php'"><i class="fas fa-user-graduate"></i> Manage Students</li>
      <li onclick="location.href='assignclasses.php'"><i class="fas fa-chart-bar"></i><strong> Assign Classes</strong></li>
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

    <!-- Assign Subject Form -->
    <div class="card form-card">
      <h4>Assign Subject to Faculty</h4>
      <?php if (isset($_SESSION['assign_error'])): ?>
  <div class="alert alert-danger"><?= $_SESSION['assign_error'] ?></div>
  <?php unset($_SESSION['assign_error']); ?>
<?php endif; ?>

      <form method="POST" class="form-section">
        <input type="hidden" name="assign_class" value="1">
        <div class="form-group">
          <label>Select Faculty</label>
          <select name="faculty_id" required>
            <option value="">Select Faculty</option>
            <?php while($f = $faculty_list->fetch_assoc()): ?>
              <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Class</label>
          <input type="text" name="class" placeholder="e.g., 1, 2, LKG" required>
        </div>
        <div class="form-group">
          <label>Section</label>
          <input type="text" name="section" pattern="[A-Za-z]" title="Section must be a single letter" placeholder="e.g., A, B" required>
        </div>
        <div class="form-group">
          <label>Select Subject</label>
          <select name="subject_id" required>
            <option value="">Select Subject</option>
            <?php $subject_list->data_seek(0); while($s = $subject_list->fetch_assoc()): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars(ucwords($s['name'])) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Day of Week</label>
          <select name="day_of_week" required>
            <option value="">Select Day</option>
            <option value="Mon">Monday</option>
            <option value="Tue">Tuesday</option>
            <option value="Wed">Wednesday</option>
            <option value="Thu">Thursday</option>
            <option value="Fri">Friday</option>
            <option value="Sat">Saturday</option>
            <option value="Sun">Sunday</option>
          </select>
        </div>
        <div class="form-group">
          <label>Start Time</label>
          <input type="time" name="start_time" required>
        </div>
        <div class="form-group">
          <label>End Time</label>
          <input type="time" name="end_time" required>
        </div>
        <button type="submit" class="save-button">Assign</button>
      </form>

              <?php if ($assignments->num_rows > 0): ?>
  <hr>
  <h5>Current Assignments</h5>
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Faculty</th>
        <th>Class</th>
        <th>Section</th>
        <th>Subject</th>
        <th>Day</th>
        <th>Time</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $assignments->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['faculty_name']) ?></td>
          <td><?= htmlspecialchars($row['class']) ?></td>
          <td><?= htmlspecialchars($row['section']) ?></td>
          <td><?= htmlspecialchars($row['subject_name']) ?></td>
          <td><?= htmlspecialchars($row['day_of_week']) ?></td>
          <td><?= htmlspecialchars(date('g:i A', strtotime($row['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($row['end_time']))) ?></td>
          <td>
            <a href="assignclasses.php?delete_assignment=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this assignment?');" class="btn btn-sm btn-danger">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
<?php else: ?>
  <p style="margin-top: 1rem;">No assignments found.</p>
<?php endif; ?>


      <button type="button" onclick="toggleNewSubjectForm()" class="btn btn-outline-secondary">Add New Subject</button>
                     <?php if (isset($_SESSION['subject_error'])): ?>
  <div class="alert alert-danger"><?= $_SESSION['subject_error'] ?></div>
  <?php unset($_SESSION['subject_error']); ?>
<?php elseif (isset($_SESSION['subject_success'])): ?>
  <div class="alert alert-success"><?= $_SESSION['subject_success'] ?></div>
  <?php unset($_SESSION['subject_success']); ?>
<?php endif; ?>
      <div id="new-subject-form" class="subject-form-flex hidden">
        <form method="POST" action="add_subject.php">
          <input type="text" name="subject_name" placeholder="Enter subject name" required>
          <input type="hidden" name="admin_id" value="<?= $admin_id ?>">
          <input type="hidden" name="is_global" value="0">
          <button type="submit" class="btn btn-sm btn-primary">Add</button>
        </form>
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

  function toggleNewSubjectForm() {
    document.getElementById("new-subject-form").classList.toggle("hidden");
  }

  window.addEventListener("click", function (e) {
    if (!e.target.closest(".icon")) {
      const dropdown = document.getElementById("profile-dropdown");
      if (dropdown) dropdown.classList.add("hidden");
    }
  });
</script>

<script src="app.js"></script>
</body>
</html>
