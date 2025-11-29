<?php
require '../../includes/db_connect.php';

// Fetch the incident to edit
if (isset($_GET['id'])) {
    $incident_id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM incidents WHERE id = ?");
    $stmt->execute([$incident_id]);
    $incident = $stmt->fetch();

    // Fetch incident types and statuses
    $incident_types_stmt = $pdo->prepare("SELECT * FROM incident_types");
    $incident_types_stmt->execute();
    $incident_types = $incident_types_stmt->fetchAll();

    $incident_statuses_stmt = $pdo->prepare("SELECT * FROM incident_statuses");
    $incident_statuses_stmt->execute();
    $incident_statuses = $incident_statuses_stmt->fetchAll();

    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get form data
        $student_id = $_POST['student_id'];
        $description = $_POST['description'];
        $incident_type_id = $_POST['incident_type_id'];
        $status_id = $_POST['status_id'];

        // Update incident data
        $stmt = $pdo->prepare("UPDATE incidents SET student_id = ?, description = ?, incident_type_id = ?, status_id = ? WHERE id = ?");
        $stmt->execute([$student_id, $description, $incident_type_id, $status_id, $incident_id]);

        // Redirect to incidents list page
        header('Location: ../reported_violation.php');
        exit();
    }
}
?>

<?php include('../includes/header1.php'); ?>




<div class="content">
    <!-- Edit Incident Form -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-warning card-header-icon">
                    <div class="card-icon">
                        <i class="material-icons">assignment</i>
                    </div>
                    <h4 class="card-title">Edit Incident</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <!--    
                    <div class="form-group">
                            <label for="student_id">Student</label>
                            <select name="student_id" id="student_id" class="form-control">
                                <?php
                                $students_stmt = $pdo->prepare("SELECT * FROM students");
                                $students_stmt->execute();
                                $students = $students_stmt->fetchAll();
                                foreach ($students as $student) {
                                    $selected = ($student['id'] == $incident['student_id']) ? 'selected' : '';
                                    echo "<option value='{$student['id']}' $selected>{$student['lastname']}, {$student['firstname']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                            -->
                        <div class="form-group">
                            <label for="student_id">Student</label>
                            <?php
                            // Get student name for display
                            $student_name = "Unknown Student";
                            if (isset($incident['student_id'])) {
                                $student_stmt = $pdo->prepare("SELECT firstname, lastname FROM students WHERE id = ?");
                                $student_stmt->execute([$incident['student_id']]);
                                $student = $student_stmt->fetch();
                                if ($student) {
                                    $student_name = $student['lastname'] . ', ' . $student['firstname'];
                                }
                            }
                            ?>
                            <input type="text" class="form-control"
                                value="<?php echo htmlspecialchars($student_name); ?>" readonly>
                            <input type="hidden" name="student_id" value="<?php echo $incident['student_id'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="description">Incident Description</label>
                            <textarea name="description" id="description" class="form-control"
                                required><?php echo htmlspecialchars($incident['description']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="incident_type_id"> </label>
                            <select name="incident_type_id" id="incident_type_id" class="form-control">
                                <?php foreach ($incident_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo ($type['id'] == $incident['incident_type_id']) ? 'selected' : ''; ?>>
                                        <?php echo $type['type_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status_id"> </label>
                            <select name="status_id" id="status_id" class="form-control">
                                <?php foreach ($incident_statuses as $status): ?>
                                    <option value="<?php echo $status['id']; ?>" <?php echo ($status['id'] == $incident['status_id']) ? 'selected' : ''; ?>>
                                        <?php echo $status['status_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>


<?php include('../includes/footer.php'); ?>