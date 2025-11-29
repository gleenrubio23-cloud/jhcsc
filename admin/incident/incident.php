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
                                <i class="material-icons">add</i> Add New Incident
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

                        </tbody>
                    </table>
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


            alert('You pressed on Row: ' + data[0] + ' ' + data[1] + ' ' + data[2]);
        });

        // Like record
        table.on('click', '.like', function () {
            alert('You clicked on Like button');
        });
    });
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