<?php
session_start();
require_once '../conn.php';

// Ensure faculty is logged in
if (!isset($_SESSION['faculty_id'])) {
  header("Location: ../login.php");
  exit();
}

$faculty_id = $_SESSION['faculty_id'];

// fetch faculty details
$faculty_name = '';
$faculty_email = '';

$stmt = $conn->prepare("SELECT name, email FROM faculty WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($name, $email);


if ($stmt->fetch()) {
    $faculty_name = $name;
    $faculty_email = $email;
} else {
    $faculty_name = "Faculty";
    $faculty_email = "faculty@college.edu";
}


$stmt->close();

// Get available class, section, course for this faculty
$classes = [];
$sections = [];
$courses = [];

$class_q = $conn->query("SELECT DISTINCT class FROM faculty_subjects WHERE faculty_id = $faculty_id");
while ($row = $class_q->fetch_assoc()) {
  $classes[] = $row['class'];
}

$section_q = $conn->query("SELECT DISTINCT section FROM faculty_subjects WHERE faculty_id = $faculty_id");
while ($row = $section_q->fetch_assoc()) {
  $sections[] = $row['section'];
}

$course_q = $conn->query("SELECT DISTINCT s.name FROM faculty_subjects fs JOIN subjects s ON fs.subject_id = s.id WHERE fs.faculty_id = $faculty_id");
while ($row = $course_q->fetch_assoc()) {
  $courses[] = $row['name'];
}

// Fetch attendance records (optional: filter logic can be added here)
$filter_class = $_GET['class'] ?? '';
$filter_section = $_GET['section'] ?? '';
$filter_course = $_GET['course'] ?? '';
$filter_date = $_GET['date'] ?? '';

$query = "
  SELECT 
    st.name AS student_name, 
    st.class, 
    st.section, 
    s.name AS subject_name, 
    a.date, 
    a.status
  FROM attendance a
  JOIN students st ON a.student_id = st.id
  JOIN subjects s ON a.subject_id = s.id
  JOIN faculty_subjects fs 
    ON fs.subject_id = s.id 
    AND fs.class = st.class 
    AND fs.section = st.section
  WHERE fs.faculty_id = ?
";

$params = [$faculty_id];
$types = "i";

if (!empty($filter_class)) {
  $query .= " AND st.class = ?";
  $params[] = $filter_class;
  $types .= "s";
}
if (!empty($filter_section)) {
  $query .= " AND st.section = ?";
  $params[] = $filter_section;
  $types .= "s";
}
if (!empty($filter_course)) {
  $query .= " AND s.name = ?";
  $params[] = $filter_course;
  $types .= "s";
}
if (!empty($filter_date)) {
  $query .= " AND a.date = ?";
  $params[] = $filter_date;
  $types .= "s";
}

$query .= " ORDER BY a.date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$attendance_q = $stmt->get_result();


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>

  <!-- ✅ Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- ✅ Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-qFeoDyAJ0M1LNUtLxVgVjNUqkA+1mJH0BML6A0VnRXA7OmlTwlUeKxN0+1bLj+gD" crossorigin="anonymous">

  <!-- ✅ Your Custom Stylesheet -->
  <link rel="stylesheet" href="facstyle.css">

  <!-- ✅ Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pDpwO12OEzKUThVBDG9YkBGBcD5/kqfZHRq/0JvvS46FuJm0kuTx9ApcoWvF5a1p" crossorigin="anonymous"></script>
</head>


<body>
  <div class="container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <ul>
  <li onclick="location.href='dashboard.php'">
    <i class="fas fa-tachometer-alt"></i> Dashboard
  </li>
  <li onclick="location.href='attendanceentry.php'">
    <i class="fas fa-edit"></i> Attendance Entry
  </li>
  <li>
    <i class="fas fa-eye"></i> <strong>View Attendance</strong>
  </li>
  <li id="logoutBtn">
  <i class="fas fa-sign-out-alt"></i> Logout
</li>

</ul>

    </div>

    <!-- Logout Confirmation Modal -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to logout?</p>
    <div class="modal-actions">
      <button id="confirmLogout">OK</button>
      <button id="cancelLogout">Cancel</button>
    </div>
  </div>
</div>


    <!-- Main Content -->
    <div class="main">
      <!-- Top Bar -->
      <div class="topbar">
        <div class="left-topbar">
          <span class="menu-button" onclick="toggleSidebar()">☰</span>
          <span class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($faculty_name); ?></strong></span>
          <span class="subwelcome">Hope your classes go smoothly today!</span>
        </div>
        <div class="right-icons">
          
          <span class="icon" onclick="showProfile()">👤</span>
        </div>
        <!-- Dropdowns -->
        <div id="notification-dropdown" class="dropdown hidden">
          <strong>Important Dates</strong>
          <ul>
            <li>✔️ Mid-Term: July 10</li>
            <li>✔️ Project Due: July 20</li>
            <li>✔️ Final Exam: Aug 5</li>
          </ul>
        </div>
        <div id="profile-dropdown" class="dropdown hidden">
          <strong>Teacher Details</strong>
          <p>Name: <?php echo htmlspecialchars($faculty_name); ?></p>
          <p>Email: <?php echo htmlspecialchars($faculty_email); ?></p>
        </div>

      </div>

      <!-- View Attendance Section -->
      <!-- View Attendance Section -->
<div id="view-attendance">
  <form method="GET">
  <div class="form-section" style="display: flex; flex-wrap: wrap; gap: 20px;">
    <div class="form-group">
      <label for="class">Class</label>
      <select id="class" name="class">
        <option value="">All</option>
        <?php foreach ($classes as $class): ?>
          <option value="<?php echo $class; ?>" <?php if (($filter_class ?? '') == $class) echo 'selected'; ?>>
            <?php echo htmlspecialchars($class); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="section">Section</label>
      <select id="section" name="section">
        <option value="">All</option>
        <?php foreach ($sections as $sec): ?>
          <option value="<?php echo $sec; ?>" <?php if (($filter_section ?? '') == $sec) echo 'selected'; ?>>
            <?php echo htmlspecialchars($sec); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="course">Subject</label>
      <select id="course" name="course">
        <option value="">All</option>
        <?php foreach ($courses as $course): ?>
          <option value="<?php echo $course; ?>" <?php if (($filter_course ?? '') == $course) echo 'selected'; ?>>
            <?php echo htmlspecialchars($course); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="date">Date</label>
      <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($filter_date ?? ''); ?>">
    </div>

    <!-- ✅ This puts button on its own row -->
    <div class="form-group" style="width: 100%; text-align: center; margin-top: 35px;">
      <button type="submit" class="btn btn-dark">Filter</button>
    </div>
  </div>
</form>

  <div class="form-group" style="max-width: 300px; margin-bottom: 20px;">
    <label for="searchInput">Search by Name</label>
    <input type="text" id="searchInput" placeholder="Enter student name..." onkeyup="filterTable()" style="padding: 10px; width: 100%; font-size: 16px; border: 1px solid #ccc; border-radius: 6px;">
  </div>

  <!-- Attendance Table -->
  <table>
    <thead>
      <tr>
        <th>NAME</th>
        <th>CLASS</th>
        <th>SECTION</th>
        <th>SUBJECT</th>
        <th>DATE</th>
        <th>STATUS</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $attendance_q->fetch_assoc()): ?>
        <tr>
          <td data-label="Name"><?php echo htmlspecialchars($row['student_name']); ?></td>
          <td data-label="Class"><?php echo htmlspecialchars($row['class']); ?></td>
          <td data-label="Section"><?php echo htmlspecialchars($row['section']); ?></td>
          <td data-label="Course"><?php echo htmlspecialchars($row['subject_name']); ?></td>
          <td data-label="Date"><?php echo htmlspecialchars($row['date']); ?></td>
          <td data-label="Status">
            <span class="badge bg-<?php echo ($row['status'] === 'Present') ? 'success' : 'failure'; ?> text-white">
              <?php echo ($row['status'] === 'Present') ? 'Present' : 'Absent'; ?>
            </span>

          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById("sidebar").classList.toggle("collapsed");
    }

    function showNotification() {
      toggleDropdown("notification-dropdown");
    }

    function showProfile() {
      toggleDropdown("profile-dropdown");
    }

    function toggleDropdown(id) {
      document.getElementById("notification-dropdown").classList.add("hidden");
      document.getElementById("profile-dropdown").classList.add("hidden");
      const dropdown = document.getElementById(id);
      dropdown.classList.toggle("hidden");
    }

    window.addEventListener("click", function (e) {
      if (!e.target.closest(".icon")) {
        document.getElementById("notification-dropdown").classList.add("hidden");
        document.getElementById("profile-dropdown").classList.add("hidden");
      }
    });

    window.onload = function () {
      const dateInput = document.getElementById("date");
      const today = new Date();
      const toDateString = (date) => date.toISOString().split("T")[0];
      dateInput.value = toDateString(today);
    };

    function filterTable() {
  const input = document.getElementById("searchInput");
  const filter = input.value.toLowerCase();
  const table = document.querySelector("#view-attendance table");
  const tr = table.getElementsByTagName("tr");

  for (let i = 1; i < tr.length; i++) { // start from 1 to skip table header
    const td = tr[i].getElementsByTagName("td")[0]; // column 0 = name
    if (td) {
      const textValue = td.textContent || td.innerText;
      tr[i].style.display = textValue.toLowerCase().includes(filter) ? "" : "none";
    }
  }
}

  </script>
  <script src="functions.js"></script>
</body>

</html>
