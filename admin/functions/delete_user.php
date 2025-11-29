<?php
require '../../includes/db_connect.php';
session_start();

// Check if user is admin
/*if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied! Admin privileges required.";
    header("Location: ../dashboard.php");
    exit();
}*/

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No user ID provided!";
    header("Location: ../users_list.php");
    exit();
}

$user_id = $_GET['id'];

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot delete your own account!";
    header("Location: ../users_list.php");
    exit();
}

try {
    // Check if user has related records in students table
    $check_stmt = $pdo->prepare("SELECT COUNT(*) as student_count FROM students WHERE id = ?");
    $check_stmt->execute([$user_id]);
    $student_count = $check_stmt->fetch()['student_count'];

    if ($student_count > 0) {
        $_SESSION['error'] = "Cannot delete user! This user has $student_count student record(s) associated. Please delete the student records first.";
        header("Location: ../users_list.php");
        exit();
    }

    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    $_SESSION['success'] = "User deleted successfully!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
}

header("Location: ../users_list.php");
exit();
?>