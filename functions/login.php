<?php
session_start();
require '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Check if the user exists in the database with student data
        $stmt = $pdo->prepare("SELECT u.*, s.id as student_id, s.firstname, s.lastname, s.course, s.year, s.section 
                              FROM users u 
                              LEFT JOIN students s ON u.username = s.username 
                              WHERE u.username = ? OR u.email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // User authenticated, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['login_time'] = time();

            // Add student data to session if user is a student
            if ($user['role'] === 'student' && $user['student_id']) {
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['course'] = $user['course'];
                $_SESSION['year'] = $user['year'];
                $_SESSION['section'] = $user['section'];
            }

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header('Location: ../admin/');
                    break;
                case 'teacher':
                    header('Location: ../teacher/index.php');
                    break;
                case 'guidance':
                    header('Location: ../guidance/index.php');
                    break;
                case 'student':
                    header('Location: ../student/');
                    break;
                default:
                    header('Location: ../index.php');
            }
            exit;
        } else {
            // Invalid login credentials
            $_SESSION['error_message'] = "Invalid login credentials!";
            header('Location: ../index.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        header('Location: ../index.php');
        exit;
    }
}
?>