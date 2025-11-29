<?php
include '../includes/db_connect.php';
// Fetch incident types and statuses
$incident_types_stmt = $pdo->prepare("SELECT * FROM incident_types");
$incident_types_stmt->execute();
$incident_types = $incident_types_stmt->fetchAll();

$incident_statuses_stmt = $pdo->prepare("SELECT * FROM incident_statuses");
$incident_statuses_stmt->execute();
$incident_statuses = $incident_statuses_stmt->fetchAll();

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
        header("Location: index.php");
        exit(); // Always use exit() after a redirect to stop further execution.

    } else {
        echo "Error creating incident.";
    }
}
?>

<?php include('includes/header.php'); ?>

<div class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-success">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="card-title">
                                <i class="material-icons">person_add</i>
                                Add New Incident
                            </h4>
                            <p class="card-category">Enter Incident Data</p>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="../studentlist.php" class="btn btn-secondary">
                                <i class="material-icons">arrow_back</i> Back to Students
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Display Messages -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <i class="material-icons">close</i>
                            </button>
                            <span><?php echo $_SESSION['error'];
                            unset($_SESSION['error']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <i class="material-icons">close</i>
                            </button>
                            <span><?php echo $_SESSION['success'];
                            unset($_SESSION['success']); ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="incidentForm" method="POST" action="">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <select name="student_id" id="student_id" class="selectpicker form-control"
                                        data-style="select-with-transition" title="Student Name" data-live-search="true"
                                        required>
                                        <!-- Fetch students dynamically -->
                                        <?php
                                        $students_stmt = $pdo->prepare("SELECT * FROM students");
                                        $students_stmt->execute();
                                        $students = $students_stmt->fetchAll();

                                        foreach ($students as $student) {
                                            echo "<option value='{$student['id']}'>{$student['lastname']}, {$student['firstname']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description">Incident Description</label>
                                    <textarea name="description" id="description" class="form-control"
                                        required></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <select name="incident_type_id" id="incident_type_id" class="selectpicker form-control"
                                    data-style="select-with-transition" title="Incident Type" required>
                                    <?php foreach ($incident_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>"><?php echo $type['type_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <select name="status_id" id="status_id" class="selectpicker form-control"
                                        data-style="select-with-transition" title="Status" required>
                                        <?php foreach ($incident_statuses as $status): ?>
                                            <option value="<?php echo $status['id']; ?>">
                                                <?php echo $status['status_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" value="Create Incident">Submit</button>
                    </form>
                </div>
            </div>

            <!-- Success Message (Initially Hidden) -->
            <div id="successMessage" class="card" style="display: none;">
                <div class="card-body text-center">
                    <h5 class="card-text">Incident successfully created!</h5>
                    <button class="btn btn-rose btn-fill" onclick="demo.showSwal('success-message')">Success!</button>
                </div>
            </div>
        </div>
    </div>

    <!--
    <form method="post">
        Student ID: <input type="text" name="student_id" required><br>
        Description: <textarea name="description" required></textarea><br>
        Incident Type ID: <input type="text" name="incident_type_id" required><br>
        Status ID: <input type="text" name="status_id" required><br>
        <input type="submit" value="Create Incident">
    </form>
                                        -->



</div>





<?php include('includes/footer.php'); ?>