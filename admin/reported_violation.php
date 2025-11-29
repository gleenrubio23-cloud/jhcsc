<?php

session_start();
require '../includes/db_connect.php'; // Ensure the correct path for db.php

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}


// Get course filter from the URL query string
$courseFilter = isset($_GET['course']) ? $_GET['course'] : '';

// Fetch incident data from MySQL
if ($courseFilter) {
    // Fetch incidents related to the selected course
    $stmt = $pdo->prepare("SELECT * FROM incidents WHERE student_id IN (SELECT id FROM students WHERE course = ?)");
    $stmt->execute([$courseFilter]);
} else {
    // If no filter is set, fetch all incidents
    $stmt = $pdo->prepare("SELECT * FROM incidents");
    $stmt->execute();
}

$incidents = $stmt->fetchAll();

// Fetch students and create an associative array with student_id as the key
$students_stmt = $pdo->prepare("SELECT * FROM students");
$students_stmt->execute();
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array

// Create a student lookup array (student_id as key)
$student_lookup = [];
foreach ($students as $student) {
    $student_lookup[$student['id']] = $student; // Use student_id as key
}

// Fetch incident types and statuses for the dropdowns
$incident_types_stmt = $pdo->prepare("SELECT * FROM incident_types");
$incident_types_stmt->execute();
$incident_types = $incident_types_stmt->fetchAll();

$incident_statuses_stmt = $pdo->prepare("SELECT * FROM incident_statuses");
$incident_statuses_stmt->execute();
$incident_statuses = $incident_statuses_stmt->fetchAll();


?>

<?php include('includes/header.php'); ?>

<div class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-warning">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="card-title">
                                <i class="material-icons">people</i>
                                Incidents
                            </h4>
                            <!--<p class="card-category">Manage system users</p>-->
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="create_incident.php" class="btn btn-secondary">
                                <i class="material-icons">add</i> Add New Incident
                            </a>
                        </div>
                    </div>
                </div>
                <!--aguy tabang mga labgit
                <div class="card-header card-header-warning card-header-icon">
                    <div class="card-icon">
                        <i class="material-icons">assignment</i>
                    </div>
                    <h4 class="card-title">Report Incidents</h4>
                </div>-->
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
                    <div class="toolbar">
                        <!--wala nehh-->

                    </div>

                </div>
                <div class="material-datatables">
                    <table id="datatables" class="table table-striped table-no-bordered table-hover" cellspacing="0"
                        width="100%" style="width:100%">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Name</th>
                                <th>Incident Description</th>
                                <th>Incident Type</th>
                                <th>Status</th>
                                <th class="disabled-sorting text-right">Actions</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>No.</th>
                                <th>Name</th>
                                <th>Incident Description</th>
                                <th>Incident Type</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php foreach ($incidents as $incident): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($incident['id']); ?></td>
                                    <td>
                                        <?php
                                        // Get the student details based on student_id
                                        $student_name = 'Unknown'; // Default value if no student is found
                                        if (isset($student_lookup[$incident['student_id']])) {
                                            $student_name = $student_lookup[$incident['student_id']]['lastname'] . ', ' . $student_lookup[$incident['student_id']]['firstname'] . ', ' . $student_lookup[$incident['student_id']]['middlename'];
                                        }
                                        echo htmlspecialchars($student_name);
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($incident['description']); ?></td>
                                    <td><?php echo htmlspecialchars($incident_types[$incident['incident_type_id'] - 1]['type_name']); ?>
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-pill
                                            <?php echo ($incident_statuses[$incident['status_id'] - 1]['status_name'] == 'First Offense') ? 'badge-info' :
                                                ($incident_statuses[$incident['status_id'] - 1]['status_name'] == 'Second Offense' ? 'badge-warning' :
                                                    ($incident_statuses[$incident['status_id'] - 1]['status_name'] == 'Third Offense' ? 'badge-danger' : 'badge-info')); ?>">
                                            <?php echo htmlspecialchars($incident_statuses[$incident['status_id'] - 1]['status_name']); ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a href="functions/edit_incident.php?id=<?php echo $incident['id']; ?>"
                                            class="btn btn-link btn-warning btn-just-icon edit">
                                            <i class="material-icons">dvr</i>
                                        </a>
                                        <a href="javascript:void(0);"
                                            onclick="confirmDelete(<?php echo $incident['id']; ?>)"
                                            class="btn btn-link btn-danger btn-just-icon remove">
                                            <i class="material-icons">close</i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog    " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">
                        Warning - Delete Incident</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="material-icons">close</i>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Warning - </strong> Are you sure you want to delete this incident?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Confirmation Modal -->





</div>

<script src="../assets/js/core/jquery.min.js"></script>
<script src="../assets/js/plugins/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function () {
        $('#datatables').DataTable({
            "pagingType": "full_numbers",
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records",
            }
        });

        var table = $('#datatables').DataTable();

        // Edit record
        table.on('click', '.edit', function () {
            $tr = $(this).closest('tr');
            var data = table.row($tr).data();
            alert('You pressed on Row: ' + data[0] + ' ' + data[1] + ' ' + data[2]);
        });

        // Delete a record
        table.on('click', '.remove', function (e) {


            alert('You pressed on Row: ' + data[0] + ' ' + data[1] + ' ' + data[2]);
        });

        // Like record
        table.on('click', '.like', function () {
            alert('You clicked on Like button');
        });
    });

    function redirectToCreate() {
        // Optional: Add any pre-redirect logic here
        window.location.href = 'create_incident.php';
    }
</script>

<script>
    function confirmDelete(incidentId) {
        // Show the confirmation modal
        $('#confirmationModal').modal('show');

        // When the user clicks "Delete" in the modal, proceed with deletion
        $('#confirmDeleteBtn').click(function () {
            // Redirect to the delete page with the incident ID
            window.location.href = "functions/delete_incident.php?id=" + incidentId;
        });
    }
</script>

<script>
    $(document).ready(function () {
        // Initialise Sweet Alert library
        demo.showSwal();
    });
</script>

<?php include('includes/footer.php'); ?>