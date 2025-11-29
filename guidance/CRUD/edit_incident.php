<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch current data for the incident
    $sql = "SELECT * FROM incidents WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $incident = $stmt->fetch();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Update query
        $student_id = $_POST['student_id'];
        $description = $_POST['description'];
        $incident_type_id = $_POST['incident_type_id'];
        $status_id = $_POST['status_id'];

        $sql = "UPDATE incidents SET student_id = :student_id, description = :description, 
                incident_type_id = :incident_type_id, status_id = :status_id WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        // Bind parameters and execute
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':incident_type_id', $incident_type_id);
        $stmt->bindParam(':status_id', $status_id);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            echo "Incident updated successfully!";
        } else {
            echo "Error updating incident.";
        }
    }
}
?>

<form method="post">
    Student ID: <input type="text" name="student_id" value="<?php echo $incident['student_id']; ?>" required><br>
    Description: <textarea name="description" required><?php echo $incident['description']; ?></textarea><br>
    Incident Type ID: <input type="text" name="incident_type_id" value="<?php echo $incident['incident_type_id']; ?>"
        required><br>
    Status ID: <input type="text" name="status_id" value="<?php echo $incident['status_id']; ?>" required><br>
    <input type="submit" value="Update Incident">
</form>