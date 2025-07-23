<?php
session_start();
require_once '../conn.php';

$admin_id = $_SESSION['user_id'];


$subject_count = $conn->query("SELECT COUNT(*) as count FROM subjects")->fetch_assoc()['count'];

$faculty_stmt = $conn->prepare("SELECT COUNT(*) as count FROM faculty WHERE admin_id = ?");
$faculty_stmt->bind_param("i", $admin_id);
$faculty_stmt->execute();
$faculty_result = $faculty_stmt->get_result();
$faculty_count = $faculty_result->fetch_assoc()['count'];

$student_stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE admin_id = ?");
$student_stmt->bind_param("i", $admin_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student_count = $student_result->fetch_assoc()['count'];


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

  
  <link rel="stylesheet" href="newstyle.css">

  <!-- ✅ Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pDpwO12OEzKUThVBDG9YkBGBcD5/kqfZHRq/0JvvS46FuJm0kuTx9ApcoWvF5a1p" crossorigin="anonymous"></script>


        <style>
            .cards {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 40px;
            /*margin-bottom: 40px;*/
            }

            .card {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            text-align: center;
            }
        </style>
</head>
<body>
<div class="container"> 

    <div class="sidebar" id="sidebar">

        <ul>
            <li onclick="location.href='dashboard.php'"><i class="fas fa-tachometer-alt"></i><strong> Dashboard</strong></li>
            <li onclick="location.href='managestudents.php'"><i class="fas fa-user-graduate"></i> Manage Students</li>
            <li onclick="location.href='assignclasses.php'"><i class="fas fa-chart-bar"></i> Assign Classes</li>
            <li onclick="location.href='managefaculty.php'"><i class="fas fa-chalkboard-teacher"></i> Manage Faculty</li> 
            <li onclick="location.href='attendance_report.php'"><i class="fas fa-file-alt"></i> Reports</li> 
            <li id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i> Logout
            </li>

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
        <div class="cards" style="flex-direction: column;">
            <div class="card">
            <h2><?= $subject_count ?></h2>
            <p>Subjects</p>
            </div>

            <div class="card">
            <h2><?= $faculty_count ?></h2>
            <p>Faculty Members</p>
            </div>
            <div class="card">
            <h2><?= $student_count ?></h2>
            <p>Registered Students</p>
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
  <script src="app.js"></script>
</body>