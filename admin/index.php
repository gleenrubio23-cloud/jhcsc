<?php
session_start();
include '../includes/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}



$admin_name = $_SESSION['username'];
$error = '';

// Initialize variables with default values to prevent undefined variable errors
$total_students = 0;
$total_teachers = 0;
$total_incidents = 0;
$pending_incidents = 0;
$recent_incidents = [];
$user_roles = [];
$course_distribution = [];
$monthly_trends = [];

try {
    // Get overall statistics
    $total_students = $pdo->query("SELECT COUNT(*) FROM students WHERE deleted = 0")->fetchColumn();
    $total_teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' ")->fetchColumn();
    $total_incidents = $pdo->query("SELECT COUNT(*) FROM incidents")->fetchColumn();
    $pending_incidents = $pdo->query("SELECT COUNT(*) FROM incidents i 
                                     JOIN incident_statuses s ON i.status_id = s.id 
                                     WHERE s.status_name = 'Pending'")->fetchColumn();

    // Get recent incidents
    $recent_incidents = $pdo->query("SELECT i.*, s.firstname, s.lastname, s.course, it.type_name, st.status_name 
                                    FROM incidents i 
                                    JOIN students s ON i.student_id = s.id 
                                    JOIN incident_types it ON i.incident_type_id = it.id 
                                    JOIN incident_statuses st ON i.status_id = st.id 
                                    ORDER BY i.created_at DESC LIMIT 10")->fetchAll();

    // Get user distribution by role
    $user_roles = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll();

    // Get course distribution
    $course_distribution = $pdo->query("SELECT course, COUNT(*) as count FROM students WHERE deleted = 0 AND course IS NOT NULL GROUP BY course")->fetchAll();

    // Get monthly incident trends (last 6 months)
    $monthly_trends = $pdo->query("SELECT 
                                  DATE_FORMAT(created_at, '%Y-%m') as month,
                                  COUNT(*) as count
                                  FROM incidents 
                                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                                  ORDER BY month DESC")->fetchAll();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Include header AFTER setting all variables
include('includes/header.php');
?>

<!-- Dashboard Content -->
<div class="content">
    <div class="container-fluid">
        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="material-icons">error</i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Welcome Header -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title">Admin Dashboard</h4>
                        <p class="card-category">Welcome back, <?php echo htmlspecialchars($admin_name); ?>! Here's your
                            system overview.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">people</i>
                        </div>
                        <p class="card-category">Total Students</p>
                        <h3 class="card-title"><?php echo $total_students; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">school</i>
                            <a href="studentList.php">View all students</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">person</i>
                        </div>
                        <p class="card-category">Teachers</p>
                        <h3 class="card-title"><?php echo $total_teachers; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">group</i>
                            <a href="users_list.php">Manage staff</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-danger card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">warning</i>
                        </div>
                        <p class="card-category">Total Incidents</p>
                        <h3 class="card-title"><?php echo $total_incidents; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">list_alt</i>
                            <a href="reported_violation.php">View incidents</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">schedule</i>
                        </div>
                        <p class="card-category">Pending Cases</p>
                        <h3 class="card-title"><?php echo $pending_incidents; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">update</i>
                            Needs review
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Analytics -->
        <div class="row">
            <!-- Incident Trends Chart -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title">Incident Trends (Last 6 Months)</h4>
                        <p class="card-category">Monthly incident reports</p>
                    </div>
                    <div class="card-body">
                        <canvas id="incidentTrendsChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- User Distribution -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header card-header-info">
                        <h4 class="card-title">User Distribution</h4>
                        <p class="card-category">By role</p>
                    </div>
                    <div class="card-body">
                        <canvas id="userDistributionChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions and Recent Activity -->
        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header card-header-success">
                        <h4 class="card-title">Quick Actions</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="functions/add_user.php" class="btn btn-primary btn-block mb-3">
                                    <i class="material-icons">person_add</i><br>
                                    Add User
                                </a>
                            </div>
                            <!--
                            <div class="col-md-6">
                                <a href="system_settings.php" class="btn btn-info btn-block mb-3">
                                    <i class="material-icons">settings</i><br>
                                    Settings
                                </a>
                            </div>-->
                            <div class="col-md-6">
                                <a href="reports.php" class="btn btn-warning btn-block mb-3">
                                    <i class="material-icons">assessment</i><br>
                                    Reports
                                </a>
                            </div>
                            <!--
                            <div class="col-md-6">
                                <a href="backup.php" class="btn btn-success btn-block mb-3">
                                    <i class="material-icons">backup</i><br>
                                    Backup
                                </a>
                            </div>-->
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="card mt-4">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">System Status</h4>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Database
                                <span class="badge badge-success badge-pill">Online</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                File Storage
                                <span class="badge badge-success badge-pill">OK</span>
                            </div>
                            <!--
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Last Backup
                                <span class="badge badge-info badge-pill"><?php echo date('M d, Y'); ?></span>
                            </div>
                            -->
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                System Load
                                <span class="badge badge-success badge-pill">Normal</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Incidents -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header card-header-danger">
                        <h4 class="card-title">Recent Incidents</h4>
                        <p class="card-category">Latest reported incidents</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_incidents)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="text-danger">
                                        <tr>
                                            <th>Date</th>
                                            <th>Student</th>
                                            <th>Course</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_incidents as $incident): ?>
                                            <tr>
                                                <td><?php echo date('M d', strtotime($incident['created_at'])); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($incident['firstname'] . ' ' . $incident['lastname']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($incident['course']); ?></td>
                                                <td><?php echo htmlspecialchars($incident['type_name']); ?></td>
                                                <td>
                                                    <span title="<?php echo htmlspecialchars($incident['description']); ?>">
                                                        <?php echo htmlspecialchars(substr($incident['description'], 0, 50)) . '...'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                    switch ($incident['status_name']) {
                                                        case 'Pending':
                                                            echo 'warning';
                                                            break;
                                                        case 'Under Review':
                                                            echo 'info';
                                                            break;
                                                        case 'Resolved':
                                                            echo 'success';
                                                            break;
                                                        default:
                                                            echo 'secondary';
                                                    }
                                                    ?>">
                                                        <?php echo htmlspecialchars($incident['status_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="functions/edit_incident.php?id=<?php echo $incident['id']; ?>"
                                                        class="btn btn-info btn-sm" title="View Details">
                                                        <i class="material-icons">visibility</i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="reported_violation.php" class="btn btn-primary">View All Incidents</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="material-icons">info</i>
                                No incidents reported recently.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Course Distribution -->
                <div class="card mt-4">
                    <div class="card-header card-header-info">
                        <h4 class="card-title">Student Distribution by Course</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (!empty($course_distribution)): ?>
                                <?php foreach ($course_distribution as $course): ?>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h3 class="text-info"><?php echo $course['count']; ?></h3>
                                                <p class="card-text"><?php echo htmlspecialchars($course['course']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center">
                                    <p class="text-muted">No course data available.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Incident Trends Chart
        const trendsCtx = document.getElementById('incidentTrendsChart').getContext('2d');
        const monthlyLabels = <?php echo json_encode(array_column(array_reverse($monthly_trends), 'month')); ?>;
        const monthlyData = <?php echo json_encode(array_column(array_reverse($monthly_trends), 'count')); ?>;

        const trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Incidents',
                    data: monthlyData,
                    borderColor: '#00bcd4',
                    backgroundColor: 'rgba(0, 188, 212, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Incidents'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        });

        // User Distribution Chart
        const userCtx = document.getElementById('userDistributionChart').getContext('2d');
        const roleLabels = <?php echo json_encode(array_column($user_roles, 'role')); ?>;
        const roleData = <?php echo json_encode(array_column($user_roles, 'count')); ?>;
        const backgroundColors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'];

        const userChart = new Chart(userCtx, {
            type: 'doughnut',
            data: {
                labels: roleLabels,
                datasets: [{
                    data: roleData,
                    backgroundColor: backgroundColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>

<?php
include('includes/footer.php');
?>