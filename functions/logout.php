<?php
// Start the session
session_start();

// Destroy all session data to log the user out
session_unset();  // Remove all session variables
session_destroy();  // Destroy the session



// Set a session variable to show a message
$_SESSION['error_message'] = "You have been logged out successfully.";

// Redirect to the login page with a message
header("Location: ../index.php");
exit();
?>