<?php
session_start();

// Assuming the user is logged in and session variables are set
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php'); // Redirect to login page if not logged in
    exit();
}

// Get user details from the session or the database
$user_id = $_SESSION['user_id'];
//$username = $_SESSION['username']; // Uncomment if needed
// You can fetch additional data from the database if needed.

include '../includes/db_connect.php';  // Ensure this path is correct

$student_id = $_SESSION['student_id'] ?? 1; // Replace with actual session variable

try {
    // Fetch violations count (using incidents table as violations)
    $violations_query = "SELECT COUNT(*) as count FROM incidents WHERE student_id = :student_id";
    $violations_stmt = $pdo->prepare($violations_query);
    $violations_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $violations_stmt->execute();
    $violations_count = $violations_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count resolved incidents
    $resolved_query = "SELECT COUNT(*) as count FROM incidents i 
                      WHERE i.student_id = :student_id 
                      AND i.status_id IN (SELECT id FROM incident_statuses WHERE status_name = 'Resolved')";
    $resolved_stmt = $pdo->prepare($resolved_query);
    $resolved_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $resolved_stmt->execute();
    $resolved_count = $resolved_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Fetch academic progress (using year from students table as progress indicator)
    $academic_query = "SELECT year, course FROM students WHERE id = :student_id";
    $academic_stmt = $pdo->prepare($academic_query);
    $academic_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $academic_stmt->execute();
    $academic_data = $academic_stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch academic progress (using year from students table as progress indicator)
    $academic_query = "SELECT year, course FROM students WHERE id = :student_id";
    $academic_stmt = $pdo->prepare($academic_query);
    $academic_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $academic_stmt->execute();
    $academic_data = $academic_stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate progress based on year (assuming 4-year program)
    $year = $academic_data['year'] ?? 1;
    $grades_progress = min(100, ($year / 4) * 100);
    $grades_progress_display = $grades_progress . '%';

    // Fetch latest incidents (violations)
    $latest_incidents_query = "SELECT i.*, 
                              it.type_name as incident_type,
                              s.status_name as status
                              FROM incidents i 
                              LEFT JOIN incident_types it ON i.incident_type_id = it.id 
                              LEFT JOIN incident_statuses s ON i.status_id = s.id 
                              WHERE i.student_id = :student_id 
                              ORDER BY i.created_at DESC 
                              LIMIT 5";
    $latest_incidents_stmt = $pdo->prepare($latest_incidents_query);
    $latest_incidents_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $latest_incidents_stmt->execute();
    $latest_incidents = $latest_incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch student information
    $student_info_query = "SELECT * FROM students WHERE id = :student_id";
    $student_info_stmt = $pdo->prepare($student_info_query);
    $student_info_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $student_info_stmt->execute();
    $student_info = $student_info_stmt->fetch(PDO::FETCH_ASSOC);



} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>


<?php include('includes/header.php'); ?>

<!-- Dashboard Content -->
<div class="content">

    <div class="container-fluid">
        <div class="row">
            <!-- Dashboard Widgets -->
            <div class="col-md-4">
                <div class="card card-chart">
                    <div class="card-header card-header-success">
                        <div class="ct-chart" id="violations-chart"></div>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title">Incidents Overview</h4>
                        <p class="card-category">Number of incidents recorded</p>
                        <p id="violations-count"><?php echo htmlspecialchars($violations_count); ?></p>
                        <!-- Fetch and display the actual number of violations -->
                    </div>
                </div>
            </div>


            <div class="col-md-4">
                <div class="card card-chart">
                    <div class="card-header card-header-info">
                        <div class="ct-chart" id="incident-chart"></div>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title">Resolved incidents</h4>
                        <p class="card-category">Number of incidents cleared</p>
                        <p id="incident-count"><?php echo htmlspecialchars($resolved_count); ?></p>
                        <!-- Fetch and display the actual number of incidents -->
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-chart">
                    <div class="card-header card-header-warning">
                        <div class="ct-chart" id="grades-chart"></div>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title">Academic Progress</h4>
                        <p class="card-category">Your latest academic performance</p>
                        <p id="grades-progress"><?php echo htmlspecialchars($grades_progress_display); ?> <small
                                class="text-muted">Year <?php echo htmlspecialchars($year); ?> -
                                <?php echo htmlspecialchars($academic_data['course'] ?? 'Not specified'); ?></small></p>
                        <!-- Fetch and display the actual grade percentage -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Violations Table 
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-info">
                        <h4 class="card-title">Your Latest Violations</h4>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead class="text-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Violation</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                               
                                <tr>
                                    <td>1</td>
                                    <td>Late Submission of Assignment</td>
                                    <td>2025-10-05</td>
                                    <td>Resolved</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Missed Class</td>
                                    <td>2025-10-02</td>
                                    <td>Pending</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>-->

    </div>

    <!-- Additional Dashboard Sections -->
    <div class="row">
        <!-- Student Information -->
        <div class="col-md-6">
            <div class="card">

                <div class="card-header card-header-primary">
                    <h4 class="card-title">Your Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <?php if (!empty($student_info['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($student_info['photo']); ?>" alt="Student Photo"
                                    class="img-fluid rounded-circle"
                                    style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                    style="width: 100px; height: 100px;">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h5><?php echo htmlspecialchars($student_info['firstname'] . ' ' . ($student_info['middlename'] ? $student_info['middlename'] . ' ' : '') . $student_info['lastname']); ?>
                            </h5>
                            <p class="mb-1"><strong>Course:</strong>
                                <?php echo htmlspecialchars($student_info['course'] ?? 'Not specified'); ?>
                            </p>
                            <p class="mb-1"><strong>Year & Section:</strong> Year
                                <?php echo htmlspecialchars($student_info['year'] ?? 'N/A'); ?> -
                                <?php echo htmlspecialchars($student_info['section'] ?? 'N/A'); ?>
                            </p>
                            <p class="mb-1"><strong>Contact:</strong>
                                <?php echo htmlspecialchars($student_info['contact'] ?? 'Not provided'); ?>
                            </p>
                            <p class="mb-0"><strong>Guardian:</strong>
                                <?php echo htmlspecialchars($student_info['guardian_name'] ?? 'Not specified'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Latest Incidents Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-info">
                    <h4 class="card-title">Your Recent Incidents</h4>
                </div>
                <div class="card-body">
                    <?php if (count($latest_incidents) > 0): ?>
                        <table class="table">
                            <thead class="text-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Incident Type</th>
                                    <th>Description</th>
                                    <th>Date Reported</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 1;
                                foreach ($latest_incidents as $incident):
                                    ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo htmlspecialchars($incident['incident_type'] ?? 'Not specified'); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $description = $incident['description'];
                                            echo strlen($description) > 50 ?
                                                substr($description, 0, 50) . '...' :
                                                $description;
                                            ?>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($incident['created_at'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                            switch ($incident['status']) {
                                                case 'Resolved':
                                                    echo 'success';
                                                    break;
                                                case 'First Offense':
                                                    echo 'warning';
                                                    break;
                                                case 'Second Offense':
                                                    echo 'info';
                                                    break;
                                                case 'Third Offense':
                                                    echo 'danger';
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
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Well done!</strong> No incidents recorded. Keep up the good behavior!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>



<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Violations/Incidents Chart
        var violationsChart = new Chartist.Line('#violations-chart', {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            series: [
                [2, 1, 3, 2, 1, 0] // Sample data - replace with actual monthly data
            ]
        }, {
            lineSmooth: Chartist.Interpolation.cardinal({
                tension: 0
            }),
            low: 0,
            high: 5,
            chartPadding: {
                top: 0,
                right: 0,
                bottom: 0,
                left: 0
            }
        });

        // Incident Status Chart
        var incidentChart = new Chartist.Bar('#incident-chart', {
            labels: ['Resolved', 'First Offense', 'Second Offense', 'Third Offense'],
            series: [
                [<?php echo $resolved_count; ?>, <?php echo $pending_count; ?>, <?php echo $active_cases_count - $pending_count; ?>]
            ]
        }, {
            distributeSeries: true,
            low: 0,
            high: <?php echo max($violations_count, 5); ?>
        });


        // Academic Progress Chart
        var gradesChart = new Chartist.Pie('#grades-chart', {
            labels: ['Completed', 'Remaining'],
            series: [<?php echo $grades_progress; ?>, <?php echo 100 - $grades_progress; ?>]
        }, {
            donut: true,
            donutWidth: 20,
            startAngle: 270,
            total: 100,
            showLabel: true
        });
    });
</script>




<?php include('includes/footer.php'); ?>