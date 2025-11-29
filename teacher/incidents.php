<?php
// incidents.php (placeholder)
include('includes/header.php');
include('../includes/db_connect.php');

/*if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit();
}*/
?>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title">Manage Incidents</h4>
                        <p class="card-category">View and manage all reported incidents</p>
                    </div>
                    <div class="card-body">
                        <p>This page will display all incidents with search, filter, and edit capabilities.</p>
                        <a href="add_incident.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Incident
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>