<?php
require '../../includes/db_connect.php';
session_start();

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No student ID provided!";
    header("Location: ../studentlist.php");
    exit();
}

$student_id = $_GET['id'];

try {
    // Soft delete student (mark as deleted instead of actually deleting)
    $stmt = $pdo->prepare("UPDATE students SET deleted = 1 WHERE id = ?");
    $stmt->execute([$student_id]);


    $_SESSION['success'] = "Student deleted successfully!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
}

header("Location: ../studentlist.php");
exit();
?>