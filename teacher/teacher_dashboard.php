<?php

// Start session at the very top
session_start();

// Check if user is logged in and is a student BEFORE any output
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit();
}

/* Session start (if not already in header)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if teacher is logged in (optional security)
if (!isset($_SESSION['teacher_id']) && basename($_SERVER['PHP_SELF']) != '../index.php') {
    header("Location: ../index.php");
    exit();
}*/
include('includes/header.php');
include('../includes/db_connect.php');


// Get teacher ID from session (assuming teacher is logged in)
$teacher_id = $_SESSION['teacher_id'] ?? 1; // Replace with actual session variable

try {
    // Total students count
    $total_students_query = "SELECT COUNT(*) as count FROM students WHERE deleted = 0";
    $total_students_stmt = $pdo->prepare($total_students_query);
    $total_students_stmt->execute();
    $total_students_count = $total_students_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total incidents count
    $total_incidents_query = "SELECT COUNT(*) as count FROM incidents";
    $total_incidents_stmt = $pdo->prepare($total_incidents_query);
    $total_incidents_stmt->execute();
    $total_incidents_count = $total_incidents_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Pending incidents count
    $pending_incidents_query = "SELECT COUNT(*) as count FROM incidents i 
                               WHERE i.status_id IN (SELECT id FROM incident_statuses WHERE status_name = 'Pending')";
    $pending_incidents_stmt = $pdo->prepare($pending_incidents_query);
    $pending_incidents_stmt->execute();
    $pending_incidents_count = $pending_incidents_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Students by course distribution
    $course_distribution_query = "SELECT course, COUNT(*) as count FROM students WHERE deleted = 0 AND course IS NOT NULL GROUP BY course";
    $course_distribution_stmt = $pdo->prepare($course_distribution_query);
    $course_distribution_stmt->execute();
    $course_distribution = $course_distribution_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent incidents with student names
    $recent_incidents_query = "SELECT i.*, 
                              it.type_name as incident_type,
                              s.status_name as status,
                              st.firstname, st.lastname, st.course, st.year
                              FROM incidents i 
                              LEFT JOIN incident_types it ON i.incident_type_id = it.id 
                              LEFT JOIN incident_statuses s ON i.status_id = s.id 
                              LEFT JOIN students st ON i.student_id = st.id 
                              ORDER BY i.created_at DESC 
                              LIMIT 10";
    $recent_incidents_stmt = $pdo->prepare($recent_incidents_query);
    $recent_incidents_stmt->execute();
    $recent_incidents = $recent_incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Incident statistics by type
    $incident_stats_query = "SELECT it.type_name as incident_type, COUNT(*) as count 
                            FROM incidents i 
                            LEFT JOIN incident_types it ON i.incident_type_id = it.id 
                            GROUP BY it.type_name 
                            ORDER BY count DESC";
    $incident_stats_stmt = $pdo->prepare($incident_stats_query);
    $incident_stats_stmt->execute();
    $incident_stats = $incident_stats_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Students with most incidents
    $top_students_incidents_query = "SELECT s.firstname, s.lastname, s.course, s.year, 
                                    COUNT(i.id) as incident_count
                                    FROM students s 
                                    LEFT JOIN incidents i ON s.id = i.student_id 
                                    WHERE s.deleted = 0 
                                    GROUP BY s.id 
                                    ORDER BY incident_count DESC 
                                    LIMIT 5";
    $top_students_incidents_stmt = $pdo->prepare($top_students_incidents_query);
    $top_students_incidents_stmt->execute();
    $top_students_incidents = $top_students_incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly incident trends (last 6 months)
    $monthly_trends_query = "SELECT 
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            COUNT(*) as count
                            FROM incidents 
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                            ORDER BY month DESC
                            LIMIT 6";
    $monthly_trends_stmt = $pdo->prepare($monthly_trends_query);
    $monthly_trends_stmt->execute();
    $monthly_trends = $monthly_trends_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!-- Dashboard Content -->
<div class="content">
    <div class="container-fluid">
        <!-- Dashboard Header 
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="page-header">
                    <h1>Teacher Dashboard</h1>
                    <p class="lead">Welcome back! Here's an overview of student incidents and activities.</p>
                </div>
            </div>
        </div>-->

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-primary">
                        <div class="ct-chart" id="studentsChart"></div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title"><?php echo htmlspecialchars($total_students_count); ?></h4>
                                <p class="card-category">Total Students</p>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-info">
                        <div class="ct-chart" id="incidentsChart"></div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title"><?php echo htmlspecialchars($total_incidents_count); ?></h4>
                                <p class="card-category">Total Incidents</p>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-warning">
                        <div class="ct-chart" id="pendingChart"></div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title"><?php echo htmlspecialchars($pending_incidents_count); ?></h4>
                                <p class="card-category">Pending Reviews</p>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-success">
                        <div class="ct-chart" id="resolvedChart"></div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title">
                                    <?php echo htmlspecialchars($total_incidents_count - $pending_incidents_count); ?>
                                </h4>
                                <p class="card-category">Cases Resolved</p>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Analytics Row -->
        <div class="row">
            <!-- Incident Trends Chart -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header card-header-info">
                        <h4 class="card-title">Incident Trends (Last 6 Months)</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="incidentTrendsChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Incident Type Distribution -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title">Incident Types</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="incidentTypeChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Incidents and Student Lists -->
        <div class="row">
            <!-- Recent Incidents Table -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">Recent Incidents</h4>
                        <p class="card-category">Latest reported incidents requiring attention</p>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_incidents) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="text-warning">
                                        <tr>
                                            <th>Student</th>
                                            <th>Course/Year</th>
                                            <th>Incident Type</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_incidents as $incident): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($incident['firstname'] . ' ' . $incident['lastname']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($incident['course'] ?? 'N/A'); ?><br>
                                                    <small class="text-muted">Year
                                                        <?php echo htmlspecialchars($incident['year'] ?? 'N/A'); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($incident['incident_type'] ?? 'Not specified'); ?>
                                                </td>
                                                <td>
                                                    <span title="<?php echo htmlspecialchars($incident['description']); ?>">
                                                        <?php
                                                        $description = $incident['description'];
                                                        echo strlen($description) > 40 ?
                                                            substr($description, 0, 40) . '...' :
                                                            $description;
                                                        ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($incident['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                    switch ($incident['status']) {
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
                                                        <?php echo htmlspecialchars($incident['status'] ?? 'Unknown'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No incidents reported recently.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Students with Most Incidents -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header card-header-danger">
                        <h4 class="card-title">Students Needing Attention</h4>
                        <p class="card-category">Students with most incidents</p>
                    </div>
                    <div class="card-body">
                        <?php if (count($top_students_incidents) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($top_students_incidents as $student): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>
                                            </h6>
                                            <span
                                                class="badge badge-danger"><?php echo htmlspecialchars($student['incident_count']); ?>
                                                incidents</span>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($student['course'] ?? 'No course'); ?> -
                                            Year <?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?></p>
                                        <small class="text-muted">Needs monitoring and support</small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                All students are doing well with minimal incidents.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-4">
                    <div class="card-header card-header-success">
                        <h4 class="card-title">Quick Actions</h4>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="add_incident.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-plus-circle text-primary"></i>
                                Report New Incident
                            </a>
                            <a href="view_students.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-list text-info"></i>
                                View All Students
                            </a>
                            <!--<a href="incidents.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-clipboard-list text-warning"></i>
                                Manage Incidents
                            </a>
                            <a href="reports.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-chart-bar text-success"></i>
                                Generate Reports
                            </a>-->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Distribution -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-info">
                        <h4 class="card-title">Student Distribution by Course</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($course_distribution as $course): ?>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <h3 class="text-info"><?php echo htmlspecialchars($course['count']); ?></h3>
                                            <p class="card-text"><?php echo htmlspecialchars($course['course']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($course_distribution) === 0): ?>
                                <div class="col-12">
                                    <p class="text-muted text-center">No course data available.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- JavaScript for Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Incident Trends Chart (Line Chart)
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

        // Incident Type Distribution (Doughnut Chart)
        const typeCtx = document.getElementById('incidentTypeChart').getContext('2d');
        const typeLabels = <?php echo json_encode(array_column($incident_stats, 'incident_type')); ?>;
        const typeData = <?php echo json_encode(array_column($incident_stats, 'count')); ?>;
        const backgroundColors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
        ];

        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeData,
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

<style>
    .card-stats .card-header {
        padding: 15px;
        min-height: 100px;
    }

    .card-stats .card-body {
        padding: 15px 20px;
    }

    .card-stats .ct-chart {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0.2;
    }

    .list-group-item {
        border: 1px solid rgba(0, 0, 0, .125);
        margin-bottom: 5px;
        border-radius: 4px;
    }

    .badge {
        font-size: 0.75em;
        padding: 0.4em 0.6em;
    }

    .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, .125);
    }
</style>

<?php
// Close connection
$pdo = null;
include('includes/footer.php');
?>