<?php
include('includes/header.php');
include('../includes/db_connect.php');

$student_id = $_GET['id'] ?? 0;

if (!$student_id) {
    header("Location: students.php");
    exit();
}

try {
    // Get student details
    $student_query = "SELECT * FROM students WHERE id = ? AND deleted = 0";
    $student_stmt = $pdo->prepare($student_query);
    $student_stmt->execute([$student_id]);
    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: students.php");
        exit();
    }

    // Get student incidents count
    $incidents_count_query = "SELECT COUNT(*) as count FROM incidents WHERE student_id = ?";
    $incidents_count_stmt = $pdo->prepare($incidents_count_query);
    $incidents_count_stmt->execute([$student_id]);
    $incidents_count = $incidents_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get recent incidents
    $recent_incidents_query = "SELECT i.*, it.type_name, ises.status_name 
                              FROM incidents i 
                              LEFT JOIN incident_types it ON i.incident_type_id = it.id 
                              LEFT JOIN incident_statuses ises ON i.status_id = ises.id 
                              WHERE i.student_id = ? 
                              ORDER BY i.created_at DESC 
                              LIMIT 5";
    $recent_incidents_stmt = $pdo->prepare($recent_incidents_query);
    $recent_incidents_stmt->execute([$student_id]);
    $recent_incidents = $recent_incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="card-title">Student Details</h4>
                                <p class="card-category">Complete student information and history</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="students.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Students
                                </a>
                                <a href="add_incident.php?student_id=<?php echo $student_id; ?>"
                                    class="btn btn-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Report Incident
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Student Information -->
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <?php if (!empty($student['photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($student['photo']); ?>" alt="Student Photo"
                                        class="img-fluid rounded-circle mb-3"
                                        style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                        style="width: 150px; height: 150px;">
                                        <i class="fas fa-user fa-3x"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <span
                                        class="badge bg-<?php echo $incidents_count > 2 ? 'danger' : ($incidents_count > 0 ? 'warning' : 'success'); ?> fs-6">
                                        <?php echo $incidents_count; ?> Incidents
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <h3><?php echo htmlspecialchars($student['firstname'] . ' ' . ($student['middlename'] ? $student['middlename'] . ' ' : '') . $student['lastname']); ?>
                                </h3>

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <p><strong>Student ID:</strong> <?php echo $student['id']; ?></p>
                                        <p><strong>Course:</strong>
                                            <?php echo htmlspecialchars($student['course'] ?? 'Not assigned'); ?></p>
                                        <p><strong>Year & Section:</strong>
                                            <?php if ($student['year']): ?>
                                                Year <?php echo htmlspecialchars($student['year']); ?>
                                                <?php if ($student['section']): ?>
                                                    - <?php echo htmlspecialchars($student['section']); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Not assigned
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Contact:</strong>
                                            <?php echo htmlspecialchars($student['contact'] ?? 'Not provided'); ?></p>
                                        <p><strong>Email/Username:</strong>
                                            <?php echo htmlspecialchars($student['username']); ?></p>
                                        <p><strong>Gender:</strong>
                                            <?php echo htmlspecialchars(ucfirst($student['gender'])); ?></p>
                                    </div>
                                </div>

                                <?php if ($student['guardian_name']): ?>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h5>Guardian Information</h5>
                                            <p><strong>Name:</strong>
                                                <?php echo htmlspecialchars($student['guardian_name']); ?></p>
                                            <p><strong>Relationship:</strong>
                                                <?php echo htmlspecialchars($student['relationship'] ?? 'Not specified'); ?>
                                            </p>
                                            <p><strong>Contact:</strong>
                                                <?php echo htmlspecialchars($student['guardian_contact'] ?? 'Not provided'); ?>
                                            </p>
                                            <p><strong>Address:</strong>
                                                <?php echo htmlspecialchars($student['address'] ?? 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Incidents -->
                        <div class="row">
                            <div class="col-12">
                                <h5>Recent Incidents</h5>
                                <?php if (count($recent_incidents) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Description</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_incidents as $incident): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, Y', strtotime($incident['created_at'])); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($incident['type_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($incident['description']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php
                                                            switch ($incident['status_name']) {
                                                                case 'Resolved':
                                                                    echo 'success';
                                                                    break;
                                                                case 'Under Review':
                                                                    echo 'warning';
                                                                    break;
                                                                case 'Pending':
                                                                    echo 'info';
                                                                    break;
                                                                default:
                                                                    echo 'secondary';
                                                            }
                                                            ?>">
                                                                <?php echo htmlspecialchars($incident['status_name']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if ($incidents_count > 5): ?>
                                        <div class="text-center">
                                            <a href="students.php?search=<?php echo urlencode($student['firstname'] . ' ' . $student['lastname']); ?>"
                                                class="btn btn-outline-primary btn-sm">
                                                View All <?php echo $incidents_count; ?> Incidents
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i>
                                        No incidents recorded for this student.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>