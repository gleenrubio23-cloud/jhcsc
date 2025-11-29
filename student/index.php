<?php
// Start session at the very top
session_start();

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Check if user is logged in and is a student BEFORE any output
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

// Include configuration and dependencies
//require_once('../includes/config.php');
include('includes/header.php');
include('../includes/db_connect.php');

// Get student ID from session - since students have their own table, we need to find the student record
//$user_id = (int) ($_SESSION['user_id'] ?? 24);
$user_id = (int) $_SESSION['user_id'];
$username = $_SESSION['username'];

// Initialize all variables with default values
$errors = [];
$student_info = [];
$latest_incidents = [];
$all_incidents = [];

// Initialize statistics variables
$total_incidents = 0;
$pending_count = 0;
$under_review_count = 0;
$resolved_count = 0;
$third_offense_count = 0;
$active_cases_count = 0;
$visible_count = 0;
$year = 1;
$grades_progress = 0;
$grades_progress_display = '0%';
$violations_count = 0;

// Define which statuses are visible to students
$visibleStatuses = [2, 4]; // Under Review (ID: 2), Third Offense (ID: 4)

try {
    // Fetch student information using username from users table
    $student_info_query = "SELECT * FROM students WHERE username = ? AND deleted = 0";
    $student_info_stmt = $pdo->prepare($student_info_query);
    $student_info_stmt->execute([$username]);
    $student_info = $student_info_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student_info) {
        $errors[] = "Student information not found.";
        throw new Exception("Student not found");
    }

    $student_id = $student_info['id'];

    // Fetch student information
    $student_info_query = "SELECT * FROM students WHERE id = :student_id";
    $student_info_stmt = $pdo->prepare($student_info_query);
    $student_info_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $student_info_stmt->execute();
    $student_info = $student_info_stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch violations count (using incidents table as violations)
    $violations_query = "SELECT COUNT(*) as count FROM incidents WHERE student_id = ?";
    $violations_stmt = $pdo->prepare($violations_query);
    $violations_stmt->execute([$student_id]);
    $violations_count = $violations_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Fetch academic progress (using year from students table as progress indicator)
    $year = $student_info['year'] ?? 1;
    $grades_progress = min(100, ($year / 4) * 100);
    $grades_progress_display = $grades_progress . '%';

    // Get student's incidents with joins - only visible statuses for active cases
    $latest_incidents_query = "
        SELECT 
            i.*,
            it.type_name,
            ist.status_name,
            u.username as teacher_name
        FROM incidents i 
        JOIN incident_types it ON i.incident_type_id = it.id
        JOIN incident_statuses ist ON i.status_id = ist.id
        JOIN users u ON i.teacher_id = u.id
        WHERE i.student_id = ? AND i.status_id IN (" . implode(',', $visibleStatuses) . ")
        ORDER BY i.created_at DESC 
        LIMIT 5
    ";
    $latest_incidents_stmt = $pdo->prepare($latest_incidents_query);
    $latest_incidents_stmt->execute([$student_id]);
    $latest_incidents = $latest_incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get ALL incidents for stats (including pending and resolved)
    $all_incidents_query = "
        SELECT i.status_id, ist.status_name 
        FROM incidents i 
        JOIN incident_statuses ist ON i.status_id = ist.id
        WHERE i.student_id = ?
    ";
    $all_incidents_stmt = $pdo->prepare($all_incidents_query);
    $all_incidents_stmt->execute([$student_id]);
    $all_incidents = $all_incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $total_incidents = count($all_incidents);

    // Count by status
    foreach ($all_incidents as $incident) {
        switch ($incident['status_name']) {
            case 'Pending':
                $pending_count++;
                break;
            case 'Under Review':
                $under_review_count++;
                break;
            case 'Resolved':
                $resolved_count++;
                break;
            case 'Third Offense':
                $third_offense_count++;
                break;
        }
    }

    // Active cases (visible to students)
    $active_cases_count = $under_review_count + $third_offense_count;

    // Visible incidents count
    $visible_count = $active_cases_count;

} catch (PDOException $e) {
    error_log("Database error in student dashboard: " . $e->getMessage());
    $errors[] = "A system error occurred. Please try again later.";
} catch (Exception $e) {
    // Already handled in the code
}
?>

<!-- Dashboard Content -->
<div class="content">
    <div class="container-fluid">
        <!-- Display Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
                <h5><i class="material-icons">error_outline</i> Please fix the following errors:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Student Profile Header 
        <div class="row">
            <div class="col-md-12">
                <div class="card card-profile" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-avatar">
                        <?php if (!empty($student_info['photo'])): ?>
                            <img src="../uploads/students/<?php echo htmlspecialchars($student_info['photo']); ?>"
                                alt="Student Photo" class="img"
                                style="width: 130px; height: 130px; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <i class="material-icons">person</i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body text-white text-center">
                        <h3 class="card-title">
                            <?php echo htmlspecialchars($student_info['firstname'] . ' ' .
                                ($student_info['middlename'] ? $student_info['middlename'] . ' ' : '') .
                                $student_info['lastname']); ?>
                        </h3>
                        <h4 class="card-category">
                            <?php echo htmlspecialchars($student_info['course'] ?? 'Not specified'); ?> -
                            Year <?php echo htmlspecialchars($year); ?>
                            <?php echo htmlspecialchars($student_info['section'] ? ' - ' . $student_info['section'] : ''); ?>
                        </h4>
                        <p class="card-description">
                            Student ID: <?php echo htmlspecialchars($student_info['username'] ?? 'N/A'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>-->

        <!-- Visibility Notice 
        <div class="alert alert-warning alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <i class="material-icons">close</i>
            </button>
            <i class="material-icons">info</i>
            <strong>Note:</strong> Only active incidents under review are visible.
            Pending reports and resolved cases are not shown to students.
        </div>-->

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">school</i>
                        </div>
                        <p class="card-category">Academic Progress</p>
                        <h3 class="card-title"><?php echo $grades_progress_display; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">trending_up</i>
                            Year <?php echo htmlspecialchars($year); ?> -
                            <?php echo htmlspecialchars($student_info['course'] ?? 'Not specified'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">assignment</i>
                        </div>
                        <p class="card-category">Total Reports</p>
                        <h3 class="card-title"><?php echo $total_incidents; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">list_alt</i> All time incident reports
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">visibility</i>
                        </div>
                        <p class="card-category">Active Cases</p>
                        <h3 class="card-title"><?php echo $visible_count; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">update</i> Currently under review
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-primary card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">check_circle</i>
                        </div>
                        <p class="card-category">Resolved</p>
                        <h3 class="card-title"><?php echo $resolved_count; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">done_all</i> Successfully resolved cases
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics 
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-info">
                        <h4 class="card-title">
                            <i class="material-icons">analytics</i>
                            Incident Statistics Overview
                        </h4>
                        <p class="card-category">Complete breakdown of your incident reports</p>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2 col-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info">
                                        <i class="material-icons">assignment</i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total</span>
                                        <span class="info-box-number"><?php echo $total_incidents; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning">
                                        <i class="material-icons">schedule</i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Pending</span>
                                        <span class="info-box-number"><?php echo $pending_count; ?></span>
                                        <small class="text-muted">(not visible)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-primary">
                                        <i class="material-icons">visibility</i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Under Review</span>
                                        <span class="info-box-number"><?php echo $under_review_count; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-danger">
                                        <i class="material-icons">warning</i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Third Offense</span>
                                        <span class="info-box-number"><?php echo $third_offense_count; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success">
                                        <i class="material-icons">check_circle</i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Resolved</span>
                                        <span class="info-box-number"><?php echo $resolved_count; ?></span>
                                        <small class="text-muted">(not visible)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success">
                                        <i class="material-icons">emoji_events</i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Clean Record</span>
                                        <span class="info-box-number">
                                            <?php echo $total_incidents == 0 ? 'Yes' : 'No'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>-->

        <!-- Active Incidents Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-warning d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title">
                                <i class="material-icons">list_alt</i>
                                My Active Incidents
                            </h4>
                            <p class="card-category">Incidents currently under review by guidance office</p>
                        </div>
                        <?php if (count($latest_incidents) > 0): ?>
                            <a href="student_violations.php" class="btn btn-warning btn-sm">
                                <i class="material-icons">view_list</i> View All Violations
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (count($latest_incidents) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="text-warning">
                                        <tr>
                                            <th>Incident Type</th>
                                            <th>Title</th>
                                            <th>Reported By</th>
                                            <!--<th>Severity</th>-->
                                            <th>Status</th>
                                            <th>Date Reported</th>
                                            <!--<th>Actions</th>-->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($latest_incidents as $incident): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-pill badge-secondary">
                                                        <?php echo htmlspecialchars($incident['type_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($incident['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted" data-toggle="tooltip"
                                                        title="<?php echo htmlspecialchars($incident['description']); ?>">
                                                        <?php
                                                        $description = $incident['description'];
                                                        echo strlen($description) > 50 ?
                                                            substr($description, 0, 50) . '...' : $description;
                                                        ?>
                                                    </small>
                                                </td>
                                                <td><?php echo htmlspecialchars($incident['teacher_name']); ?></td>
                                                <!-- <td>
                                                    <?php
                                                    $severity_class = match (strtolower($incident['severity'])) {
                                                        'high' => 'danger',
                                                        'medium' => 'warning',
                                                        'low' => 'success',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge badge-pill badge-<?php echo $severity_class; ?>">
                                                        <?php echo htmlspecialchars($incident['severity']); ?>
                                                    </span>
                                                </td>-->
                                                <td>
                                                    <?php
                                                    $status_class = match ($incident['status_name']) {
                                                        'Under Review' => 'primary',
                                                        'Third Offense' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge badge-pill badge-<?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($incident['status_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="material-icons" style="font-size: 14px;">event</i>
                                                        <?php echo date('M j, Y', strtotime($incident['created_at'])); ?>
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo date('g:i A', strtotime($incident['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <!-- <td>
                                                    <button class="btn btn-info btn-sm view-incident"
                                                        data-id="<?php echo $incident['id']; ?>" data-toggle="tooltip"
                                                        title="View Details">
                                                        <i class="material-icons">visibility</i>
                                                    </button>
                                                </td>-->
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="material-icons text-success" style="font-size: 64px;">check_circle</i>
                                <h4 class="text-success">No Active Violations</h4>
                                <p class="text-muted">
                                    <?php if ($total_incidents > 0): ?>
                                        You have <?php echo $total_incidents; ?> reported incident(s),
                                        but <?php echo $resolved_count; ?> are resolved and <?php echo $pending_count; ?> are
                                        pending review.
                                    <?php else: ?>
                                        Keep up the good work! You have no reported incidents.
                                    <?php endif; ?>
                                </p>
                                <?php if ($resolved_count > 0): ?>
                                    <div class="alert alert-success mt-3" style="display: inline-block;">
                                        <i class="material-icons">check_circle</i>
                                        ✅ <?php echo $resolved_count; ?> case(s) successfully resolved
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Information & Quick Stats -->
        <div class="row mt-4">
            <!-- Student Information -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title">
                            <i class="material-icons">person</i>
                            Student Information
                        </h4>
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
                                <?php if (is_array($student_info)): ?>
                                    <h5>
                                        <?php
                                        echo htmlspecialchars(
                                            $student_info['firstname'] . ' ' .
                                            (!empty($student_info['middlename']) ? $student_info['middlename'] . ' ' : '') .
                                            $student_info['lastname']
                                        );
                                        ?>
                                    </h5>
                                <?php else: ?>
                                    <h5>Your Account has been deleted. Contact the administrator</h5>
                                <?php endif; ?>

                                <p class="mb-1"><strong>Course:</strong>
                                    <?php echo htmlspecialchars($student_info['course'] ?? 'Not specified'); ?></p>
                                <p class="mb-1"><strong>Year & Section:</strong> Year
                                    <?php echo htmlspecialchars($student_info['year'] ?? 'N/A'); ?> -
                                    <?php echo htmlspecialchars($student_info['section'] ?? 'N/A'); ?>
                                </p>
                                <p class="mb-1"><strong>Contact:</strong>
                                    <?php echo htmlspecialchars($student_info['contact'] ?? 'Not provided'); ?></p>
                                <p class="mb-0"><strong>Guardian:</strong>
                                    <?php echo htmlspecialchars($student_info['guardian_name'] ?? 'Not specified'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Progress -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-success">
                        <h4 class="card-title">
                            <i class="material-icons">trending_up</i>
                            Academic Progress
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="progress-container">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="progress-label">Year <?php echo $year; ?> Progress</span>
                                <span class="progress-percentage"><?php echo $grades_progress_display; ?></span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar"
                                    style="width: <?php echo $grades_progress; ?>%"
                                    aria-valuenow="<?php echo $grades_progress; ?>" aria-valuemin="0"
                                    aria-valuemax="100">
                                    <?php echo $grades_progress_display; ?>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                Progress through your
                                <?php echo htmlspecialchars($student_info['course'] ?? 'course'); ?> program
                                (4-year program)
                            </small>
                        </div>

                        <div class="mt-4">
                            <h6>Program Information</h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h5 mb-0"><?php echo $year; ?></div>
                                        <small>Current Year</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h5 mb-0">4</div>
                                        <small>Total Years</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h5 mb-0"><?php echo (4 - $year); ?></div>
                                        <small>Years Left</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Incident Details Modal -->
<div class="modal fade" id="incidentModal" tabindex="-1" role="dialog" aria-labelledby="incidentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="incidentModalLabel">Incident Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="incidentDetails">
                <!-- Details will be loaded here via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Auto-hide alerts after 5 seconds
        setTimeout(function () {
            $('.alert').fadeOut('slow');
        }, 5000);

        // View incident details
        $('.view-incident').on('click', function () {
            const incidentId = $(this).data('id');
            loadIncidentDetails(incidentId);
        });

        // Load incident details via AJAX
        function loadIncidentDetails(incidentId) {
            $.ajax({
                url: 'ajax/get_incident_details.php',
                type: 'GET',
                data: { id: incidentId },
                success: function (response) {
                    $('#incidentDetails').html(response);
                    $('#incidentModal').modal('show');
                },
                error: function () {
                    $('#incidentDetails').html(
                        '<div class="alert alert-danger">Failed to load incident details. Please try again.</div>'
                    );
                    $('#incidentModal').modal('show');
                }
            });
        }
    });

    // Add some custom styles
    const style = document.createElement('style');
    style.textContent = `
        .card-profile .card-avatar {
            max-width: 130px;
            max-height: 130px;
            margin: -50px auto 0;
        }
        .avatar-placeholder {
            width: 130px;
            height: 130px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        .avatar-placeholder .material-icons {
            font-size: 64px;
            color: white;
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .info-box-icon {
            float: left;
            width: 60px;
            height: 60px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .info-box-content {
            margin-left: 75px;
        }
        .info-box-text {
            font-size: 14px;
            color: #6c757d;
        }
        .info-box-number {
            font-size: 24px;
            font-weight: bold;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.04);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
        .progress-container {
            padding: 10px;
        }
    `;
    document.head.appendChild(style);
</script>

<?php
// Close connection
$pdo = null;
include('includes/footer.php');
?>