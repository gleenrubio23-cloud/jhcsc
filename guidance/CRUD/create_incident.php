<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $description = $_POST['description'];
    $incident_type_id = $_POST['incident_type_id'];
    $status_id = $_POST['status_id'];

    // Prepare SQL
    $sql = "INSERT INTO incidents (student_id, description, incident_type_id, status_id) 
            VALUES (:student_id, :description, :incident_type_id, :status_id)";
    $stmt = $pdo->prepare($sql);

    // Bind parameters and execute
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':incident_type_id', $incident_type_id);
    $stmt->bindParam(':status_id', $status_id);

    if ($stmt->execute()) {
        // Redirect to another page after successful insert
        header("Location: ../index.php"); // Corrected redirect format
        exit; // Stop further script execution after redirect
    } else {
        echo "Error creating incident.";
    }
}
?>

<form method="post">
    Student ID: <input type="text" name="student_id" required><br>
    Description: <textarea name="description" required></textarea><br>
    Incident Type ID: <input type="text" name="incident_type_id" required><br>
    Status ID: <input type="text" name="status_id" required><br>
    <input type="submit" value="Create Incident">
</form>