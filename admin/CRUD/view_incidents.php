<?php
include 'db_connect.php';

$sql = "SELECT * FROM incidents";
$stmt = $pdo->query($sql);
?>

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
                    <div class="row">
                        <!-- Add New Incident Button -->
                        <div class="col-md-6 ml-auto">
                            <button class="btn btn-info btn-round float-right" data-toggle="modal"
                                data-target="#incidentModal">
                                <i class="material-icons" href="create_incident.php">add</i> Add New Incident
                            </button>
                        </div>
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
                            <?php
                            while ($row = $stmt->fetch()) {
                                echo "<tr>
                                    <td>" . $row['id'] . "</td>
                                    <td>" . $row['student_id'] . "</td>
                                    <td>" . $row['description'] . "</td>
                                    <td>" . $row['incident_type_id'] . "</td>
                                    <td>" . $row['status_id'] . "</td>
                                    <td class='text-right'>
                                        <a href='edit_incident.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm'>Edit</a>
                                        <button class='btn btn-danger btn-sm' onclick='confirmDelete(" . $row['id'] . ")'>Delete</button>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this incident?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Add New Incident Modal -->
<div class="modal fade" id="incidentModal" tabindex="-1" role="dialog" aria-labelledby="incidentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="incidentModalLabel">Add New Incident</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="ind.php">
                    <div class="form-group">
                        <label for="student_id">Student ID:</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea class="form-control" id="description" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="incident_type_id">Incident Type ID:</label>
                        <input type="text" class="form-control" id="incident_type_id" name="incident_type_id" required>
                    </div>
                    <div class="form-group">
                        <label for="status_id">Status ID:</label>
                        <input type="text" class="form-control" id="status_id" name="status_id" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_new_incident" class="btn btn-primary">Save Incident</button>
                    </div>
                </form>
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
    });

    function confirmDelete(incidentId) {
        // Show the confirmation modal
        $('#confirmationModal').modal('show');

        // When the user clicks "Delete" in the modal, proceed with deletion
        $('#confirmDeleteBtn').click(function () {
            // Redirect to the delete page with the incident ID
            window.location.href = "CRUD/delete_incident.php?id=" + incidentId;
        });
    }
</script>