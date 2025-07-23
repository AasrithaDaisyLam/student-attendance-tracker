<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['faculty_id'])) {
  header("Location: ../login.php");
  exit();
}

$faculty_id = $_SESSION['faculty_id'];
$faculty_name = "Faculty";
$faculty_email = "faculty@college.edu";

$stmt = $conn->prepare("SELECT name, email FROM faculty WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($faculty_name, $faculty_email);
$stmt->fetch();
$stmt->close();

$classes = [];
$sections = [];
$subjects = [];

$today = date('D'); // Example: Mon, Tue, etc.

$res = $conn->query("SELECT DISTINCT class FROM faculty_subjects WHERE faculty_id = $faculty_id AND day_of_week = '$today'");
while ($row = $res->fetch_assoc()) {
  $classes[] = $row['class'];
}

$res = $conn->query("SELECT DISTINCT section FROM faculty_subjects WHERE faculty_id = $faculty_id AND day_of_week = '$today'");
while ($row = $res->fetch_assoc()) {
  $sections[] = $row['section'];
}

$res = $conn->query("
  SELECT fs.subject_id, s.name 
  FROM faculty_subjects fs 
  JOIN subjects s ON fs.subject_id = s.id 
  WHERE fs.faculty_id = $faculty_id AND fs.day_of_week = '$today'
");
while ($row = $res->fetch_assoc()) {
  $subjects[] = $row;
}


$selected_class = $_POST['class'] ?? '';
$selected_section = $_POST['section'] ?? '';
$selected_subject_id = $_POST['subject_id'] ?? '';
$selected_date = $_POST['date'] ?? date('Y-m-d');

$attendance_exists = false;

if ($selected_class && $selected_section && $selected_subject_id && $selected_date && isset($_POST['load_students'])) {
  $stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE subject_id = ? AND class = ? AND section = ? AND date = ?");
  $stmt->bind_param("isss", $selected_subject_id, $selected_class, $selected_section, $selected_date);
  $stmt->execute();
  $stmt->bind_result($count);
  $stmt->fetch();
  $stmt->close();

  if ($count > 0) {
    $attendance_exists = true;
  }
}

$students = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_students'])) {
  if ($selected_class && $selected_section) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE class = ? AND section = ?");
    $stmt->bind_param("ss", $selected_class, $selected_section);
    $stmt->execute();
    $students = $stmt->get_result();
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
  $class = $_POST['class'];
  $section = $_POST['section'];
  $subject_id = $_POST['subject_id'];
  $date = $_POST['date'];
  $today = date('Y-m-d');

if ($date !== $today) {
  $success_message = "❌ You can only submit attendance for today.";
} else {
  $stmt = $conn->prepare("SELECT id FROM students WHERE class = ? AND section = ?");
  $stmt->bind_param("ss", $class, $section);
  $stmt->execute();
  $result = $stmt->get_result();

  $present_students = $_POST['students'] ?? [];

  while ($row = $result->fetch_assoc()) {
    $student_id = $row['id'];
    $status = isset($present_students[$student_id]) ? 'Present' : 'Absent';

    $stmt_insert = $conn->prepare("INSERT INTO attendance (student_id, subject_id, class, section, date, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("iissss", $student_id, $subject_id, $class, $section, $date, $status);
    $stmt_insert->execute();
  }

  $success_message = "Attendance saved successfully.";
}

  
}

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
    <li onclick="showEntryAttendance()">
        <i class="fas fa-edit"></i> <strong>Attendance Entry</strong>
    </li>
    <li onclick="location.href='viewattendance.php'">
        <i class="fas fa-eye"></i> View Attendance
    </li>
    <li id="logoutBtn">
  <i class="fas fa-sign-out-alt"></i> Logout
</li>

</ul>

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
                  <!-- Notification icon with badge -->

                    <span class="icon" onclick="showProfile()">👤</span>
                </div>
                <!-- Notification Dropdown -->
                <div id="notification-dropdown" class="dropdown hidden">
                    <strong>Important Dates</strong>
                    <ul>
                        <li>✔️ Mid-Term: July 10</li>
                        <li>✔️ Project Due: July 20</li>
                        <li>✔️ Final Exam: Aug 5</li>
                    </ul>
                </div>
                
                <!-- Profile Dropdown -->
                <div id="profile-dropdown" class="dropdown hidden">
                  <strong>Teacher Details</strong>
                  <p>Name: <?php echo htmlspecialchars($faculty_name); ?></p>
                  <p>Email: <?php echo htmlspecialchars($faculty_email); ?></p>
                </div>


            </div>
              
            <!-- Entry Attendance Section -->
            <!-- Entry Attendance Section -->
            <div id="entry-attendance">
              <?php if (!empty($success_message)): ?>
                <div class="alert alert-success text-center" role="alert">
                  <?php echo $success_message; ?>
                </div>
              <?php endif; ?>
              <form method="POST">
                <!-- <input type="hidden" name="submit_attendance" value="1"> -->

                <!-- Form Section (1 row layout) -->
                <div class="form-section">
                    <div class="form-group">
                        <label for="class">Class</label>
                        <select id="class" name="class" required>
                          <option value="">Select</option>
                          <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class; ?>" <?php if ($class == $selected_class) echo 'selected'; ?>>
                              <?php echo strtoupper($class); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>

                    </div>

                    <div class="form-group">
                        <label for="section">Section</label>
                        <select id="section" name="section" required>
                          <option value="">Select</option>
                          <?php foreach ($sections as $section): ?>
                            <option value="<?php echo $section; ?>" <?php if ($section == $selected_section) echo 'selected'; ?>>
                              <?php echo strtoupper($section); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>

                    </div>

                    <div class="form-group">
                        <label for="subject_id">Subject</label>
                        <select id="subject_id" name="subject_id" required>
                          <option value="">Select</option>
                          <?php foreach ($subjects as $sub): ?>
                            <option value="<?php echo $sub['subject_id']; ?>" <?php if ($sub['subject_id'] == $selected_subject_id) echo 'selected'; ?>>
                              <?php echo htmlspecialchars($sub['name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>

                    </div>

                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" required />
                    </div>
                </div>
                 <div style="text-align: center; margin-top: 10px;">
                    <button type="submit" name="load_students" class="btn btn-secondary">Load Students</button>
                  </div>

                <!-- Attendance Table -->
                  <?php if ($students && $students->num_rows > 0): ?>
                    <?php if ($attendance_exists): ?>
                      <div class="alert alert-warning text-center" role="alert">
                        Attendance for this subject, class, section, and date is already submitted.
                      </div>
                    <?php else: ?>
                      <table>
                        <thead>
                          <tr>
                            <th>NAME</th>
                            <th>CLASS</th>
                            <th>SECTION</th>
                            <th>SUBJECT</th>
                            <th>PRESENT/ABSENT</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($stu = $students->fetch_assoc()): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($stu['name']); ?></td>
                            <td><?php echo htmlspecialchars($stu['class']); ?></td>
                            <td><?php echo htmlspecialchars($stu['section']); ?></td>
                            <td><?php echo htmlspecialchars($subjects[array_search($selected_subject_id, array_column($subjects, 'subject_id'))]['name']); ?></td>
                            <td>
                              <input type="checkbox" name="students[<?php echo $stu['id']; ?>]" value="Present" checked />
                            </td>
                          </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    <?php endif; ?>
                    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_students'])): ?>
                    <p style="text-align:center; margin-top:20px;">No students found for selected class and section.</p>
                  <?php endif; ?>



                <!-- Save Button at Bottom -->
                  <div style="text-align: center;">
                    <?php if ($attendance_exists): ?>
                      <button class="save-button btn btn-secondary" type="button" disabled>
                        Attendance Already Submitted
                      </button>
                    <?php else: ?>
                      <button class="save-button" type="submit" name="submit_attendance" value="1">
                        SAVE ATTENDANCE
                      </button>
                    <?php endif; ?>
                  </div>

             </div>
             </form>
        </div>

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


    <!-- JavaScript -->
    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("collapsed");
        }

      //   function submitAttendance() {
      // alert("Attendance submitted!");
      //   }

        function showNotification() {
                toggleDropdown("notification-dropdown");
            }

            function showProfile() {
                toggleDropdown("profile-dropdown");
            }

            function toggleDropdown(id) {
                // Hide both first
                document.getElementById("notification-dropdown").classList.add("hidden");
                document.getElementById("profile-dropdown").classList.add("hidden");

                // Toggle only the clicked one
                const dropdown = document.getElementById(id);
                dropdown.classList.toggle("hidden");
            }

            // Hide dropdowns when clicking anywhere else
            window.addEventListener("click", function (e) {
                if (!e.target.closest(".icon")) {
                    document.getElementById("notification-dropdown").classList.add("hidden");
                    document.getElementById("profile-dropdown").classList.add("hidden");
                }
            });


        function showEntryAttendance() {
            document.getElementById("entry-attendance").scrollIntoView({ behavior: "smooth" });
        }
    // Example: Change count dynamically
    function updateNotificationCount(count) {
        const badge = document.getElementById("notif-count");
        badge.textContent = count;
        badge.style.display = count > 0 ? "inline-block" : "none";
    }

    // You can call this later like:
    updateNotificationCount(0);  // hides badge
     // shows "5"

        // Restrict date to today and past 7 days
        window.onload = function () {
          const dateInput = document.getElementById("date");
          const today = new Date();
          const toDateString = (date) => date.toISOString().split("T")[0];
          const todayStr = toDateString(today);

          dateInput.min = todayStr;
          dateInput.max = todayStr;
          dateInput.value = todayStr;
        };

    </script>
    <script src="functions.js"></script>

</body>
<script>
  setTimeout(() => {
    const alertBox = document.querySelector('.alert-success');
    if (alertBox) alertBox.style.display = 'none';
  }, 3000);
</script>
</html><?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['faculty_id'])) {
  header("Location: ../login.php");
  exit();
}

$faculty_id = $_SESSION['faculty_id'];
$faculty_name = "Faculty";
$faculty_email = "faculty@college.edu";

$stmt = $conn->prepare("SELECT name, email FROM faculty WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($faculty_name, $faculty_email);
$stmt->fetch();
$stmt->close();

$classes = [];
$sections = [];
$subjects = [];

$today = date('D'); // Example: Mon, Tue, etc.

$res = $conn->query("SELECT DISTINCT class FROM faculty_subjects WHERE faculty_id = $faculty_id AND day_of_week = '$today'");
while ($row = $res->fetch_assoc()) {
  $classes[] = $row['class'];
}

$res = $conn->query("SELECT DISTINCT section FROM faculty_subjects WHERE faculty_id = $faculty_id AND day_of_week = '$today'");
while ($row = $res->fetch_assoc()) {
  $sections[] = $row['section'];
}

$res = $conn->query("
  SELECT fs.subject_id, s.name 
  FROM faculty_subjects fs 
  JOIN subjects s ON fs.subject_id = s.id 
  WHERE fs.faculty_id = $faculty_id AND fs.day_of_week = '$today'
");
while ($row = $res->fetch_assoc()) {
  $subjects[] = $row;
}


$selected_class = $_POST['class'] ?? '';
$selected_section = $_POST['section'] ?? '';
$selected_subject_id = $_POST['subject_id'] ?? '';
$selected_date = $_POST['date'] ?? date('Y-m-d');

$attendance_exists = false;

if ($selected_class && $selected_section && $selected_subject_id && $selected_date && isset($_POST['load_students'])) {
  $stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE subject_id = ? AND class = ? AND section = ? AND date = ?");
  $stmt->bind_param("isss", $selected_subject_id, $selected_class, $selected_section, $selected_date);
  $stmt->execute();
  $stmt->bind_result($count);
  $stmt->fetch();
  $stmt->close();

  if ($count > 0) {
    $attendance_exists = true;
  }
}

$students = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_students'])) {
  if ($selected_class && $selected_section) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE class = ? AND section = ?");
    $stmt->bind_param("ss", $selected_class, $selected_section);
    $stmt->execute();
    $students = $stmt->get_result();
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
  $class = $_POST['class'];
  $section = $_POST['section'];
  $subject_id = $_POST['subject_id'];
  $date = $_POST['date'];
  $today = date('Y-m-d');

if ($date !== $today) {
  $success_message = "❌ You can only submit attendance for today.";
} else {
  $stmt = $conn->prepare("SELECT id FROM students WHERE class = ? AND section = ?");
  $stmt->bind_param("ss", $class, $section);
  $stmt->execute();
  $result = $stmt->get_result();

  $present_students = $_POST['students'] ?? [];

  while ($row = $result->fetch_assoc()) {
    $student_id = $row['id'];
    $status = isset($present_students[$student_id]) ? 'Present' : 'Absent';

    $stmt_insert = $conn->prepare("INSERT INTO attendance (student_id, subject_id, class, section, date, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("iissss", $student_id, $subject_id, $class, $section, $date, $status);
    $stmt_insert->execute();
  }

  $success_message = "Attendance saved successfully.";
}

  
}

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
    <li onclick="showEntryAttendance()">
        <i class="fas fa-edit"></i> <strong>Attendance Entry</strong>
    </li>
    <li onclick="location.href='viewattendance.php'">
        <i class="fas fa-eye"></i> View Attendance
    </li>
    <li id="logoutBtn">
  <i class="fas fa-sign-out-alt"></i> Logout
</li>

</ul>

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
                  <!-- Notification icon with badge -->

                    <span class="icon" onclick="showProfile()">👤</span>
                </div>
                <!-- Notification Dropdown -->
                <div id="notification-dropdown" class="dropdown hidden">
                    <strong>Important Dates</strong>
                    <ul>
                        <li>✔️ Mid-Term: July 10</li>
                        <li>✔️ Project Due: July 20</li>
                        <li>✔️ Final Exam: Aug 5</li>
                    </ul>
                </div>
                
                <!-- Profile Dropdown -->
                <div id="profile-dropdown" class="dropdown hidden">
                  <strong>Teacher Details</strong>
                  <p>Name: <?php echo htmlspecialchars($faculty_name); ?></p>
                  <p>Email: <?php echo htmlspecialchars($faculty_email); ?></p>
                </div>


            </div>
              
            <!-- Entry Attendance Section -->
            <!-- Entry Attendance Section -->
            <div id="entry-attendance">
              <?php if (!empty($success_message)): ?>
                <div class="alert alert-success text-center" role="alert">
                  <?php echo $success_message; ?>
                </div>
              <?php endif; ?>
              <form method="POST">
                <!-- <input type="hidden" name="submit_attendance" value="1"> -->

                <!-- Form Section (1 row layout) -->
                <div class="form-section">
                    <div class="form-group">
                        <label for="class">Class</label>
                        <select id="class" name="class" required>
                          <option value="">Select</option>
                          <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class; ?>" <?php if ($class == $selected_class) echo 'selected'; ?>>
                              <?php echo strtoupper($class); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>

                    </div>

                    <div class="form-group">
                        <label for="section">Section</label>
                        <select id="section" name="section" required>
                          <option value="">Select</option>
                          <?php foreach ($sections as $section): ?>
                            <option value="<?php echo $section; ?>" <?php if ($section == $selected_section) echo 'selected'; ?>>
                              <?php echo strtoupper($section); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>

                    </div>

                    <div class="form-group">
                        <label for="subject_id">Subject</label>
                        <select id="subject_id" name="subject_id" required>
                          <option value="">Select</option>
                          <?php foreach ($subjects as $sub): ?>
                            <option value="<?php echo $sub['subject_id']; ?>" <?php if ($sub['subject_id'] == $selected_subject_id) echo 'selected'; ?>>
                              <?php echo htmlspecialchars($sub['name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>

                    </div>

                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" required />
                    </div>
                </div>
                 <div style="text-align: center; margin-top: 10px;">
                    <button type="submit" name="load_students" class="btn btn-secondary">Load Students</button>
                  </div>

                <!-- Attendance Table -->
                  <?php if ($students && $students->num_rows > 0): ?>
                    <?php if ($attendance_exists): ?>
                      <div class="alert alert-warning text-center" role="alert">
                        Attendance for this subject, class, section, and date is already submitted.
                      </div>
                    <?php else: ?>
                      <table>
                        <thead>
                          <tr>
                            <th>NAME</th>
                            <th>CLASS</th>
                            <th>SECTION</th>
                            <th>SUBJECT</th>
                            <th>PRESENT/ABSENT</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($stu = $students->fetch_assoc()): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($stu['name']); ?></td>
                            <td><?php echo htmlspecialchars($stu['class']); ?></td>
                            <td><?php echo htmlspecialchars($stu['section']); ?></td>
                            <td><?php echo htmlspecialchars($subjects[array_search($selected_subject_id, array_column($subjects, 'subject_id'))]['name']); ?></td>
                            <td>
                              <input type="checkbox" name="students[<?php echo $stu['id']; ?>]" value="Present" checked />
                            </td>
                          </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    <?php endif; ?>
                    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_students'])): ?>
                    <p style="text-align:center; margin-top:20px;">No students found for selected class and section.</p>
                  <?php endif; ?>



                <!-- Save Button at Bottom -->
                  <div style="text-align: center;">
                    <?php if ($attendance_exists): ?>
                      <button class="save-button btn btn-secondary" type="button" disabled>
                        Attendance Already Submitted
                      </button>
                    <?php else: ?>
                      <button class="save-button" type="submit" name="submit_attendance" value="1">
                        SAVE ATTENDANCE
                      </button>
                    <?php endif; ?>
                  </div>

             </div>
             </form>
        </div>

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


    <!-- JavaScript -->
    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("collapsed");
        }

      //   function submitAttendance() {
      // alert("Attendance submitted!");
      //   }

        function showNotification() {
                toggleDropdown("notification-dropdown");
            }

            function showProfile() {
                toggleDropdown("profile-dropdown");
            }

            function toggleDropdown(id) {
                // Hide both first
                document.getElementById("notification-dropdown").classList.add("hidden");
                document.getElementById("profile-dropdown").classList.add("hidden");

                // Toggle only the clicked one
                const dropdown = document.getElementById(id);
                dropdown.classList.toggle("hidden");
            }

            // Hide dropdowns when clicking anywhere else
            window.addEventListener("click", function (e) {
                if (!e.target.closest(".icon")) {
                    document.getElementById("notification-dropdown").classList.add("hidden");
                    document.getElementById("profile-dropdown").classList.add("hidden");
                }
            });


        function showEntryAttendance() {
            document.getElementById("entry-attendance").scrollIntoView({ behavior: "smooth" });
        }
    // Example: Change count dynamically
    function updateNotificationCount(count) {
        const badge = document.getElementById("notif-count");
        badge.textContent = count;
        badge.style.display = count > 0 ? "inline-block" : "none";
    }

    // You can call this later like:
    updateNotificationCount(0);  // hides badge
     // shows "5"

        // Restrict date to today and past 7 days
        window.onload = function () {
          const dateInput = document.getElementById("date");
          const today = new Date();
          const toDateString = (date) => date.toISOString().split("T")[0];
          const todayStr = toDateString(today);

          dateInput.min = todayStr;
          dateInput.max = todayStr;
          dateInput.value = todayStr;
        };

    </script>
    <script src="functions.js"></script>

</body>
<script>
  setTimeout(() => {
    const alertBox = document.querySelector('.alert-success');
    if (alertBox) alertBox.style.display = 'none';
  }, 3000);
</script>
</html>