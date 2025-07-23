<?php
session_start();
require_once '../conn.php'; // adjust path if needed

$faculty_id = $_SESSION['faculty_id'];

// fetch faculty details
$faculty_name = "Faculty";
$faculty_email = "faculty@college.edu";

$stmt = $conn->prepare("SELECT name, email FROM faculty WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($faculty_name, $faculty_email);
$stmt->fetch();
$stmt->close();

// total courses
// total courses
$stmt = $conn->prepare("SELECT COUNT(DISTINCT subject_id) FROM faculty_subjects WHERE faculty_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($total_courses);
$stmt->fetch();
$stmt->close();


// total sections (based on section + subject_id combination)
// total section-class combinations
$stmt = $conn->prepare("SELECT COUNT(DISTINCT CONCAT(class, '-', section)) FROM faculty_subjects WHERE faculty_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($total_sections);
$stmt->fetch();
$stmt->close();


// today’s classes
$day = date('D');
$stmt = $conn->prepare("SELECT COUNT(*) FROM faculty_subjects WHERE faculty_id = ? AND day_of_week = ?");
$stmt->bind_param("is", $faculty_id, $day);
$stmt->execute();
$stmt->bind_result($todays_classes);
$stmt->fetch();
$stmt->close();

// attendance pending
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM faculty_subjects fs
    LEFT JOIN attendance a 
    ON fs.subject_id = a.subject_id 
        AND fs.section = a.section 
        AND a.date = CURDATE()
    WHERE fs.faculty_id = ? AND fs.day_of_week = ? AND a.id IS NULL
");
$stmt->bind_param("is", $faculty_id, $day);
$stmt->execute();
$stmt->bind_result($attendance_pending);
$stmt->fetch();
$stmt->close();

// today’s schedule
$schedule = [];
$stmt = $conn->prepare("
    SELECT fs.subject_id, fs.section, fs.class, fs.start_time, fs.end_time, s.name 
    FROM faculty_subjects fs
    JOIN subjects s ON fs.subject_id = s.id
    WHERE fs.faculty_id = ? AND fs.day_of_week = ?
    ORDER BY fs.start_time
");

$stmt->bind_param("is", $faculty_id, $day);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $schedule[] = $row;
}
$stmt->close();

// ✅ Now loop through schedule to check attendance
foreach ($schedule as &$item) {
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE subject_id = ? AND class = ? AND section = ? AND date = CURDATE()");
    $stmt_check->bind_param("iss", $item['subject_id'], $item['class'], $item['section']);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();
//<div class="schedule-item">
    $item['attendance_taken'] = ($count > 0);
}
unset($item);


function ordinal($number) {
  if (!in_array(($number % 100), [11, 12, 13])) {
    switch ($number % 10) {
      case 1: return $number . 'st';
      case 2: return $number . 'nd';
      case 3: return $number . 'rd';
    }
  }
  return $number . 'th';
}

function formatClassDisplay($class) {
  $lower = strtolower($class);
  if (in_array($lower, ['lkg', 'ukg', 'nursery'])) {
    return strtoupper($class); // show as LKG, UKG, etc.
  }
  return ordinal((int)$class) . ' Class';
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

  <style>
    .cards {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      margin-bottom: 40px;
    }

    .card {
      flex: 1;
      min-width: 180px;
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
      text-align: center;
    }

    .section {
      margin-bottom: 40px;
    }

    .section h3 {
      margin-bottom: 12px;
      color: #1f2937;
    }

    .list {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    }

    .list-item {
      padding: 10px 0;
      border-bottom: 1px solid #e5e7eb;
      color: #374151;
    }

    .list-item:last-child {
      border-bottom: none;
    }
  </style>
</head>
<body>
<div class="container"> 
  <div class="sidebar" id="sidebar">
    <ul>
      <li><i class="fas fa-tachometer-alt"></i> <strong>Dashboard</strong></li>
      <li onclick="location.href='attendanceentry.php'"><i class="fas fa-edit"></i> Attendance Entry</li>
      <li onclick="location.href='viewattendance.php'"><i class="fas fa-eye"></i> View Attendance</li>
      <li id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</li>
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

  <div class="main">
    <div class="topbar">
      <div class="left-topbar">
        <span class="menu-button" onclick="toggleSidebar()">☰</span>
        <span class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($faculty_name); ?></strong></span>
        <span class="subwelcome">Hope your classes go smoothly today!</span>

      </div>

      <div class="right-icons">
        <span class="icon" onclick="showProfile()">👤</span>
      </div>

      <!-- ✅ PHP-INJECTED TEACHER DETAILS -->
       <div id="profile-dropdown" class="dropdown hidden">
          <strong>Teacher Details</strong>
          <p>Name: <?php echo htmlspecialchars($faculty_name); ?></p>
          <p>Email: <?php echo htmlspecialchars($faculty_email); ?></p>
      </div>
    </div>

    <div class="cards">
      <div class="card">
        <h2><?php echo $total_courses; ?></h2>
        <p>Total Subjects</p>
      </div>

      <div class="card">
        <h2><?php echo $total_sections; ?></h2>
        <p>Assigned Sections</p>
      </div>

      <div class="card">
        <h2><?php echo $todays_classes; ?></h2>
        <p>Today’s Classes</p>
      </div>

      <div class="card">
        <h2><?php echo $attendance_pending; ?></h2>
        <p>Attendance Pending</p>
      </div>
    </div>


    <div class="row">
      <!-- Today's Schedule -->
      <div class="half-width">
        <div class="section-card">
          <div class="section-header">
            <h4><i class="fas fa-calendar-day"></i> Today's Schedule</h4>
            <span id="current-date" class="date-display"></span>
          </div>
          <div class="schedule-list">
              <?php if (empty($schedule)): ?>
                <p>No classes scheduled today.</p>
              <?php else: ?>
                <?php
                      // function forceToPmSafe($time) {
                      //   $parts = explode(':', $time);
                      //   $hour = (int)$parts[0];
                      //   if ($hour > 0 && $hour < 12) {
                      //     $hour += 12;
                      //   }
                      //   return sprintf('%02d:%s:%s', $hour, $parts[1], $parts[2]);
                      // }
                      ?>

                      <?php foreach ($schedule as $item): ?>
                        <div class="schedule-item">
                          <div class="time">
                            <?php
                              echo date("g:i A", strtotime($item['start_time']));
                            ?>
                          </div>

                          <div class="details">
                            <?php
                              $classLabel = ctype_digit($item['class']) ? ordinal((int)$item['class']) : strtoupper($item['class']);
                              $section = strtoupper($item['section']);
                              $subject = htmlspecialchars($item['name']);
                            ?>
                            <strong>
                              <?php echo "$classLabel Class - Section $section - $subject"; ?>
                              <?php if ($item['attendance_taken']): ?>
                                <span style="color: green;">✔ Attendance Taken</span>
                              <?php endif; ?>
                            </strong><br>
                            <small class="text-muted">
                              <?php
                                echo date("g:i A", strtotime($item['start_time'])) . " - " . date("g:i A", strtotime($item['end_time']));
                              ?>
                            </small>
                          </div>
                        </div>


                      <?php endforeach; ?>


              <?php endif; ?>
          </div>

        </div>
      </div>
    </div>

  </div>
</div>

<script>
  const heading = document.getElementById("schedule-heading");
  const today = new Date();
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  const formattedDate = today.toLocaleDateString('en-US', options);
  if (heading) heading.textContent = `${formattedDate} Schedule`;

  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
  }
  function showProfile() {
    toggleDropdown("profile-dropdown");
  }

  function toggleDropdown(id) {
    const notif = document.getElementById("notification-dropdown");
    const profile = document.getElementById("profile-dropdown");

    if (notif) notif.classList.add("hidden");
    if (profile) profile.classList.add("hidden");

    const dropdown = document.getElementById(id);
    if (dropdown) dropdown.classList.toggle("hidden");
  }

  window.addEventListener("click", function (e) {
    if (!e.target.closest(".icon")) {
      const dropdown = document.getElementById("profile-dropdown");
      if (dropdown) dropdown.classList.add("hidden");
    }
  });
</script>
<script src="functions.js"></script>
</body>
</html>
