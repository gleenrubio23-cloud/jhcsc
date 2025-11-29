<?php

session_start();
require '../includes/db_connect.php'; // Ensure the correct path for db.php

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

<?php
// Start session
//session_start();
require '../includes/db_connect.php'; // Ensure the correct path for db.php

// Function to get incidents with status filter
function getIncidents($pdo, $courseFilter = '')
{
    $sql = "
        SELECT incidents.*, incident_statuses.status_name, students.firstname, students.lastname
        FROM incidents
        LEFT JOIN incident_statuses ON incidents.status_id = incident_statuses.id
        LEFT JOIN students ON incidents.student_id = students.id
        WHERE (:status_name = '' OR incident_statuses.status_name = :status_name)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['status_name' => $courseFilter]);
    return $stmt->fetchAll();
}

// Fetch incidents based on the filter, if provided
$courseFilter = isset($_GET['status_name']) ? $_GET['status_name'] : '';
$incidents = getIncidents($pdo, $courseFilter);

// Fetch incident types and statuses for the dropdowns (if needed for later use)
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
                <div class="card-header card-header-warning card-header-icon">
                    <div class="card-icon">
                        <i class="material-icons">assignment</i>
                    </div>
                    <h4 class="card-title">Report Incidents</h4>
                </div>
                <div class="card-body">
                    <div class="toolbar">
                        <!-- You can add extra buttons/actions here -->
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
                                        <td><?php echo htmlspecialchars($incident['lastname'] . ', ' . $incident['firstname']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($incident['description']); ?></td>
                                        <td><?php echo htmlspecialchars($incident_types[$incident['incident_type_id'] - 1]['type_name']); ?>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-pill 
                                            <?php echo ($incident['status_name'] == 'Resolved') ? 'badge-success' :
                                                ($incident['status_name'] == 'Unresolved' ? 'badge-danger' : 'badge-warning'); ?>">
                                                <?php echo htmlspecialchars($incident['status_name']); ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="edit_incident.php?id=<?php echo $incident['id']; ?>"
                                                class="btn btn-link btn-warning btn-just-icon edit">
                                                <i class="material-icons">dvr</i>
                                            </a>
                                            <a href="delete_incident.php?id=<?php echo $incident['id']; ?>"
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
    </div>


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
            $tr = $(this).closest('tr');
            table.row($tr).remove().draw();
            e.preventDefault();
        });

        // Like record
        table.on('click', '.like', function () {
            alert('You clicked on Like button');
        });
    });
</script>

<?php include('includes/footer.php'); ?>