<?php
require '../../includes/db_connect.php';

// Check if ID is provided in the URL
if (isset($_GET['id'])) {
    $incident_id = $_GET['id'];

    // Print the id to ensure it's being passed correctly (for debugging)
    error_log('Deleting incident with ID: ' . $incident_id);

    // Prepare the SQL DELETE query
    $stmt = $pdo->prepare("DELETE FROM incidents WHERE id = ?");

    // Execute the query with the provided incident ID
    $stmt->execute([$incident_id]);

    // Check if deletion was successful
    if ($stmt->rowCount() > 0) {
        // If deletion was successful, retain the URL parameters and redirect back
        $queryString = $_SERVER['QUERY_STRING']; // Get the current query string
        header('Location: ../index.php?'); // Redirect with query parameters
        exit();
    } else {
        echo "Error: Incident could not be deleted.";
    }
} else {
    echo "Error: No incident ID provided.";
}
?>