<?php
session_start();
require_once '../conn.php';

$admin_id = $_SESSION['user_id'];

$report = "";

// Fetch class & section options for the form
$classOptions = $conn->query("SELECT DISTINCT class FROM students WHERE admin_id = $admin_id ORDER BY class");
$sectionOptions = $conn->query("SELECT DISTINCT section FROM students WHERE admin_id = $admin_id ORDER BY section");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $from = $_POST["from"];
    $to = $_POST["to"];
    $class = $_POST["class"] ?? '';
    $section = $_POST["section"] ?? '';

    $conditions = "a.date BETWEEN '$from' AND '$to' AND s.admin_id = $admin_id";
    if ($class !== '') $conditions .= " AND s.class = '$class'";
    if ($section !== '') $conditions .= " AND s.section = '$section'";

    $sql = "
    SELECT s.name, s.roll_number, s.class, s.section, COUNT(*) AS total_classes,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
        ROUND(SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) AS percentage
    FROM attendance a
    JOIN students s ON s.id = a.student_id
    WHERE $conditions
    GROUP BY s.id
    ORDER BY s.class, s.section, s.roll_number";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $report .= "<h4 class='mt-4 text-center'>Attendance Summary<br><small>($from to $to)</small></h4>";
        $report .= "<div class='table-responsive'><table class='table table-bordered mt-3'>
        <thead class='table-dark'>
        <tr>
            <th>Name</th>
            <th>Roll No</th>
            <th>Class</th>
            <th>Section</th>
            <th>Total</th>
            <th>Present</th>
            <th>%</th>
        </tr></thead><tbody>";
        while ($row = $result->fetch_assoc()) {
            $percentage = $row['percentage'];
            $style = $percentage < 75 ? "style='background-color: #f8d7da;'" : "";
            $report .= "<tr $style>
                <td>{$row['name']}</td>
                <td>{$row['roll_number']}</td>
                <td>{$row['class']}</td>
                <td>{$row['section']}</td>
                <td>{$row['total_classes']}</td>
                <td>{$row['present_count']}</td>
                <td>{$row['percentage']}%</td>
            </tr>";
        }
        $report .= "</tbody></table></div>";
    } else {
        $report = "<div class='alert alert-warning mt-3'>No data found for selected range.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="newstyle.css">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    .container-box {
      max-width: 1100px;
      margin: 50px auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
    }
    .go-back-btn {
      margin-top: 10px;
    }
  </style>
</head>
<body>
<div class="container-box">
  <h2>Attendance Report</h2>

  <!-- Go Back Button -->
  <div class="mb-3">
    <a href="dashboard.php" class="btn btn-secondary go-back-btn">&larr; Go Back</a>
  </div>

  <!-- Filter Form -->
  <form method="POST" class="row g-3 justify-content-center">
    <div class="col-md-3">
      <label for="from" class="form-label">From Date</label>
      <input type="date" id="from" name="from" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label for="to" class="form-label">To Date</label>
      <input type="date" id="to" name="to" class="form-control" required>
    </div>
    <div class="col-md-2">
      <label for="class" class="form-label">Class</label>
      <select name="class" id="class" class="form-select">
        <option value="">All</option>
        <?php while ($row = $classOptions->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($row['class']) ?>"><?= htmlspecialchars($row['class']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label for="section" class="form-label">Section</label>
      <select name="section" id="section" class="form-select">
        <option value="">All</option>
        <?php while ($row = $sectionOptions->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($row['section']) ?>"><?= htmlspecialchars($row['section']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100">Generate</button>
    </div>
  </form>

  <!-- Attendance Report Output -->
  <?= $report ?>
</div>
</body>
</html>
