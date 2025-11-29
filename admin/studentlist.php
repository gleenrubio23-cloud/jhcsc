<?php
session_start();
require '../includes/db_connect.php'; // Ensure the correct path for db.php

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}


// Get year filter from the URL query string
$yearFilter = isset($_GET['year']) ? $_GET['year'] : '';

// Fetch students based on the year filter
if ($yearFilter) {
    // Fetch students of the selected year
    $stmt = $pdo->prepare("SELECT * FROM students WHERE year = ?");
    $stmt->execute([$yearFilter]);
} else {
    // If no filter is set, fetch all students
    $stmt = $pdo->prepare("SELECT * FROM students");
    $stmt->execute();
}
if ($yearFilter) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE year = ? AND deleted = 0");
    $stmt->execute([$yearFilter]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE deleted = 0");
    $stmt->execute();
}


$students = $stmt->fetchAll();
?>

<?php include('includes/header.php'); ?>

<div class="content">

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-info">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="card-title">
                                <i class="material-icons">assignment</i>
                                List of Students
                            </h4>

                        </div>
                        <div class="col-md-6 text-right">
                            <a href="functions/add_student.php" class="btn btn-secondary"><i
                                    class="material-icons">add</i> New</a>
                        </div>
                    </div>
                </div>
                <!-- sample pa jud nehhh aguy hahah
                <div class="card-header card-header-info card-header-icon">
                    <div class="card-icon">
                        <i class="material-icons">assignment</i>
                    </div>
                    <h4 class="card-title">List of Students</h4>
                </div>
-->
                <div class="card-body">
                    <div class="toolbar">
                        <!-- Year wala na jud neh
                        <div class="row">
                            <div class="col-md">
                                <label for="yearFilter">Filter by Year:</label>
                                <select id="yearFilter" class="form-control"
                                    style="width: auto; display: inline-block;">
                                    <option value="">All</option>
                                    <option value="1">First Year</option>
                                    <option value="2">Second Year</option>
                                    <option value="3">Third Year</option>
                                    <option value="4">Fourth Year</option>
                                </select>
                            </div>
                            <div class="col-md ml-auto">
                                <a href="functions/add_student.php"
                                    class="btn btn-success btn-round float-right pull-right mr-3"><i
                                        class="material-icons">add</i> New</a>
                            </div>
                        </div>
-->

                    </div>

                    <div class="material-datatables">
                        <table id="datatables1" class="table table-striped table-no-bordered table-hover"
                            cellspacing="0" width="100%" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Year</th>
                                    <th>Email</th>
                                    <th class="disabled-sorting text-right">Actions</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Year</th>
                                    <th>Email</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </tfoot>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['lastname'] . ', ' . $student['firstname'] . ' ' . $student['middlename']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                                        <td><?php echo htmlspecialchars($student['section']); ?></td>
                                        <td><?php echo htmlspecialchars($student['year']); ?></td>
                                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                                        <td class="text-right">
                                            <!-- Edit and Delete actions -->
                                            <a href="functions/edit_student.php?id=<?php echo $student['id']; ?>"
                                                class="btn btn-link btn-warning btn-just-icon edit">
                                                <i class="material-icons">dvr</i>
                                            </a>
                                            <a href="functions/delete_student.php?id=<?php echo $student['id']; ?>"
                                                class="btn btn-link btn-danger btn-just-icon remove"
                                                onclick="return confirm('Are you sure you want to delete this student?');">
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
        // Initialize the DataTable
        var table = $('#datatables1').DataTable({
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

        // Filter by Year
        $('#yearFilter').on('change', function () {
            var selectedYear = $(this).val();
            table.column(4).search(selectedYear ? '^' + selectedYear + '$' : '', true, false).draw(); // Year column index is 4
        });

        // Add Edit functionality
        /*$('#datatables1').on('click', '.edit', function () {
            var studentId = $(this).closest('tr').find('td:first').text();
            window.location.href = 'edit_student.php?id=' + studentId;
        });

        // Add Delete functionality
        $('#datatables1').on('click', '.remove', function (e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this student?')) {
                var studentId = $(this).closest('tr').find('td:first').text();
                window.location.href = 'functions/delete_student.php?id=' + studentId;
            }
        });*/

    });
</script>



<?php include('includes/footer.php'); ?>