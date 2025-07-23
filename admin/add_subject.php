<?php
session_start();
require_once '../conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject_name'])) {
    $subject_name = trim($_POST['subject_name']);
    $admin_id = $_SESSION['user_id'];  // ensure admin_id is in session
    $is_global = 0; // admin-specific subject

    if (!empty($subject_name) && is_numeric($admin_id)) {
        // Check if the subject already exists for this admin or globally
        $check = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND (admin_id = ? OR is_global = 1)");
        $check->bind_param("si", $subject_name, $admin_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            // Add new subject
            $stmt = $conn->prepare("INSERT INTO subjects (name, admin_id, is_global) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $subject_name, $admin_id, $is_global);
            $stmt->execute();
            $stmt->close();

            $_SESSION['subject_success'] = "Subject '$subject_name' added successfully.";
        } else {
            $_SESSION['subject_error'] = "Subject '$subject_name' already exists.";
        }

        $check->close();
    } else {
        $_SESSION['subject_error'] = "Invalid subject name or admin ID.";
    }
}

header("Location: assignclasses.php");
exit();
<?php
session_start();
require_once '../conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject_name'])) {
    $subject_name = trim($_POST['subject_name']);
    $admin_id = $_SESSION['user_id'];  // ensure admin_id is in session
    $is_global = 0; // admin-specific subject

    if (!empty($subject_name) && is_numeric($admin_id)) {
        // Check if the subject already exists for this admin or globally
        $check = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND (admin_id = ? OR is_global = 1)");
        $check->bind_param("si", $subject_name, $admin_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            // Add new subject
            $stmt = $conn->prepare("INSERT INTO subjects (name, admin_id, is_global) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $subject_name, $admin_id, $is_global);
            $stmt->execute();
            $stmt->close();

            $_SESSION['subject_success'] = "Subject '$subject_name' added successfully.";
        } else {
            $_SESSION['subject_error'] = "Subject '$subject_name' already exists.";
        }

        $check->close();
    } else {
        $_SESSION['subject_error'] = "Invalid subject name or admin ID.";
    }
}

header("Location: assignclasses.php");
exit();
