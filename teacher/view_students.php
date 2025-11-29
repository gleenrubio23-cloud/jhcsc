<?php 
include('includes/header.php');
include('../includes/db_connect.php');
/*
// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit();
}*/

$success = '';
$error = '';
$search = '';
$course_filter = '';
$year_filter = '';
$status_filter = '';

// Handle search and filters
if ($_GET) {
    $search = $_GET['search'] ?? '';
    $course_filter = $_GET['course_filter'] ?? '';
    $year_filter = $_GET['year_filter'] ?? '';
    $status_filter = $_GET['status_filter'] ?? '';
}

try {
    // Build query with filters
    $query = "SELECT s.*, 
              COUNT(i.id) as incident_count
              FROM students s 
              LEFT JOIN incidents i ON s.id = i.student_id 
              WHERE s.deleted = 0";
    
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (s.firstname LIKE :search OR s.lastname LIKE :search OR s.course LIKE :search OR s.username LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($course_filter)) {
        $query .= " AND s.course = :course";
        $params[':course'] = $course_filter;
    }
    
    if (!empty($year_filter)) {
        $query .= " AND s.year = :year";
        $params[':year'] = $year_filter;
    }
    
    $query .= " GROUP BY s.id";
    
    // Add status filter
    if (!empty($status_filter)) {
        switch($status_filter) {
            case 'no_incidents':
                $query .= " HAVING incident_count = 0";
                break;
            case 'with_incidents':
                $query .= " HAVING incident_count > 0";
                break;
            case 'high_risk':
                $query .= " HAVING incident_count > 2";
                break;
        }
    }
    
    $query .= " ORDER BY s.firstname, s.lastname";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique courses and years for filters
    $courses_query = "SELECT DISTINCT course FROM students WHERE deleted = 0 AND course IS NOT NULL ORDER BY course";
    $courses_stmt = $pdo->prepare($courses_query);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $years_query = "SELECT DISTINCT year FROM students WHERE deleted = 0 AND year IS NOT NULL ORDER BY year";
    $years_stmt = $pdo->prepare($years_query);
    $years_stmt->execute();
    $years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get total statistics
    $stats_query = "SELECT 
                    COUNT(*) as total_students,
                    SUM(CASE WHEN incident_count = 0 THEN 1 ELSE 0 END) as no_incidents,
                    SUM(CASE WHEN incident_count > 0 THEN 1 ELSE 0 END) as with_incidents,
                    SUM(CASE WHEN incident_count > 2 THEN 1 ELSE 0 END) as high_risk
                    FROM (
                        SELECT s.id, COUNT(i.id) as incident_count
                        FROM students s 
                        LEFT JOIN incidents i ON s.id = i.student_id 
                        WHERE s.deleted = 0
                        GROUP BY s.id
                    ) as student_incidents";
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $students = [];
    $courses = [];
    $years = [];
    $stats = ['total_students' => 0, 'no_incidents' => 0, 'with_incidents' => 0, 'high_risk' => 0];
}
?>

<!-- Page Content -->
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="card-title">All Students</h4>
                                <p class="card-category">Complete list of all students in the system</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="add_incident.php" class="btn btn-success">
                                    <i class="fas fa-plus-circle"></i> Report Incident
                                </a>
                                <a href="teacher_dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Success/Error Messages -->
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> 
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> 
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card card-stats">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="icon-big text-center icon-warning">
                                                    <i class="fas fa-users text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="col-7">
                                                <div class="numbers">
                                                    <p class="card-category">Total Students</p>
                                                    <h4 class="card-title"><?php echo $stats['total_students']; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-stats">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="icon-big text-center icon-warning">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                            <div class="col-7">
                                                <div class="numbers">
                                                    <p class="card-category">No Incidents</p>
                                                    <h4 class="card-title"><?php echo $stats['no_incidents']; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-stats">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="icon-big text-center icon-warning">
                                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                                </div>
                                            </div>
                                            <div class="col-7">
                                                <div class="numbers">
                                                    <p class="card-category">With Incidents</p>
                                                    <h4 class="card-title"><?php echo $stats['with_incidents']; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-stats">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="icon-big text-center icon-warning">
                                                    <i class="fas fa-times-circle text-danger"></i>
                                                </div>
                                            </div>
                                            <div class="col-7">
                                                <div class="numbers">
                                                    <p class="card-category">High Risk</p>
                                                    <h4 class="card-title"><?php echo $stats['high_risk']; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search and Filter Form -->
                        <form method="GET" action="" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" placeholder="Search students..." 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-outline-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="course_filter">
                                        <option value="">All Courses</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo htmlspecialchars($course); ?>" 
                                                <?php echo $course_filter == $course ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="year_filter">
                                        <option value="">All Years</option>
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?php echo htmlspecialchars($year); ?>" 
                                                <?php echo $year_filter == $year ? 'selected' : ''; ?>>
                                                Year <?php echo htmlspecialchars($year); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="status_filter">
                                        <option value="">All Status</option>
                                        <option value="no_incidents" <?php echo $status_filter == 'no_incidents' ? 'selected' : ''; ?>>No Incidents</option>
                                        <option value="with_incidents" <?php echo $status_filter == 'with_incidents' ? 'selected' : ''; ?>>With Incidents</option>
                                        <option value="high_risk" <?php echo $status_filter == 'high_risk' ? 'selected' : ''; ?>>High Risk</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-filter"></i> Apply Filters
                                    </button>
                                    <a href="view_students.php" class="btn btn-outline-secondary">Clear</a>
                                    <button type="button" class="btn btn-info" onclick="printStudentList()">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Students Grid/Table View Toggle -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary active" id="tableViewBtn">
                                        <i class="fas fa-table"></i> Table View
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="gridViewBtn">
                                        <i class="fas fa-th"></i> Grid View
                                    </button>
                                </div>
                                <span class="float-end text-muted">
                                    Showing <?php echo count($students); ?> of <?php echo $stats['total_students']; ?> students
                                </span>
                            </div>
                        </div>

                        <!-- Table View -->
                        <div id="tableView">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="studentsTable">
                                    <thead class="text-primary">
                                        <tr>
                                            <th>Photo</th>
                                            <th>Student Name</th>
                                            <th>Course</th>
                                            <th>Year & Section</th>
                                            <th>Gender</th>
                                            <th>Contact</th>
                                            <th>Incidents</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($students) > 0): ?>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($student['photo'])): ?>
                                                            <img src="<?php echo htmlspecialchars($student['photo']); ?>" 
                                                                 alt="Student Photo" 
                                                                 class="student-photo rounded-circle"
                                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="student-photo-placeholder rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                                                 style="width: 40px; height: 40px;">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($student['firstname'] . ' ' . ($student['middlename'] ? $student['middlename'] . ' ' : '') . $student['lastname']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">ID: <?php echo $student['id']; ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($student['course'] ?? 'Not assigned'); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($student['year']): ?>
                                                            Year <?php echo htmlspecialchars($student['year']); ?>
                                                            <?php if ($student['section']): ?>
                                                                - <?php echo htmlspecialchars($student['section']); ?>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars(ucfirst($student['gender'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($student['contact']): ?>
                                                            <?php echo htmlspecialchars($student['contact']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">No contact</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $student['incident_count'] > 0 ? 'warning' : 'success'; ?>">
                                                            <?php echo $student['incident_count']; ?> incidents
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $student['incident_count'] > 2 ? 'danger' : ($student['incident_count'] > 0 ? 'warning' : 'success'); ?>">
                                                            <?php echo $student['incident_count'] > 2 ? 'High Risk' : ($student['incident_count'] > 0 ? 'Monitor' : 'Good'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="student_details.php?id=<?php echo $student['id']; ?>" 
                                                               class="btn btn-info btn-sm" 
                                                               title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="add_incident.php?student_id=<?php echo $student['id']; ?>" 
                                                               class="btn btn-warning btn-sm" 
                                                               title="Report Incident">
                                                                <i class="fas fa-exclamation-triangle"></i>
                                                            </a>
                                                            <button type="button" 
                                                                    class="btn btn-primary btn-sm view-incidents"
                                                                    data-student-id="<?php echo $student['id']; ?>"
                                                                    data-student-name="<?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>"
                                                                    title="View Incidents">
                                                                <i class="fas fa-list"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-users fa-3x mb-3"></i>
                                                        <h5>No students found</h5>
                                                        <p><?php echo !empty($search) ? 'Try adjusting your search criteria' : 'No students available in the system'; ?></p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Grid View -->
                        <div id="gridView" style="display: none;">
                            <div class="row">
                                <?php if (count($students) > 0): ?>
                                    <?php foreach ($students as $student): ?>
                                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                            <div class="card student-card h-100">
                                                <div class="card-body text-center">
                                                    <!-- Student Photo -->
                                                    <div class="mb-3">
                                                        <?php if (!empty($student['photo'])): ?>
                                                            <img src="<?php echo htmlspecialchars($student['photo']); ?>" 
                                                                 alt="Student Photo" 
                                                                 class="student-photo rounded-circle mb-2"
                                                                 style="width: 80px; height: 80px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="student-photo-placeholder rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-2"
                                                                 style="width: 80px; height: 80px;">
                                                                <i class="fas fa-user fa-2x"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <!-- Student Info -->
                                                    <h6 class="card-title mb-1">
                                                        <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>
                                                    </h6>
                                                    <p class="card-text text-muted small mb-1">
                                                        ID: <?php echo $student['id']; ?>
                                                    </p>
                                                    <p class="card-text mb-1">
                                                        <strong><?php echo htmlspecialchars($student['course'] ?? 'No Course'); ?></strong>
                                                    </p>
                                                    <p class="card-text text-muted small mb-2">
                                                        Year <?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?> 
                                                        <?php if ($student['section']): ?>
                                                            - <?php echo htmlspecialchars($student['section']); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                    
                                                    <!-- Incidents and Status -->
                                                    <div class="mb-3">
                                                        <span class="badge bg-<?php echo $student['incident_count'] > 0 ? 'warning' : 'success'; ?> mb-1">
                                                            <?php echo $student['incident_count']; ?> incidents
                                                        </span>
                                                        <br>
                                                        <span class="badge bg-<?php echo $student['incident_count'] > 2 ? 'danger' : ($student['incident_count'] > 0 ? 'warning' : 'success'); ?>">
                                                            <?php echo $student['incident_count'] > 2 ? 'High Risk' : ($student['incident_count'] > 0 ? 'Monitor' : 'Good'); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <!-- Contact -->
                                                    <?php if ($student['contact']): ?>
                                                        <p class="card-text small mb-2">
                                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['contact']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Actions -->
                                                    <div class="btn-group w-100" role="group">
                                                        <a href="student_details.php?id=<?php echo $student['id']; ?>" 
                                                           class="btn btn-info btn-sm" 
                                                           title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="add_incident.php?student_id=<?php echo $student['id']; ?>" 
                                                           class="btn btn-warning btn-sm" 
                                                           title="Report Incident">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-primary btn-sm view-incidents"
                                                                data-student-id="<?php echo $student['id']; ?>"
                                                                data-student-name="<?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>"
                                                                title="View Incidents">
                                                            <i class="fas fa-list"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-users fa-3x mb-3"></i>
                                            <h5>No students found</h5>
                                            <p><?php echo !empty($search) ? 'Try adjusting your search criteria' : 'No students available in the system'; ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Export Options -->
                        <div class="row mt-4">
                            <div class="col-md-12 text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-success" onclick="exportToCSV()">
                                        <i class="fas fa-file-csv"></i> Export to CSV
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="exportToPDF()">
                                        <i class="fas fa-file-pdf"></i> Export to PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incidents Modal -->
<div class="modal fade" id="incidentsModal" tabindex="-1" aria-labelledby="incidentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="incidentsModalLabel">Student Incidents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="incidentsModalBody">
                <!-- Incidents will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // View incidents button handler
    const viewIncidentButtons = document.querySelectorAll('.view-incidents');
    viewIncidentButtons.forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const studentName = this.getAttribute('data-student-name');
            
            // Show loading
            document.getElementById('incidentsModalBody').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading incidents...</p>
                </div>
            `;
            
            // Set modal title
            document.getElementById('incidentsModalLabel').textContent = `Incidents - ${studentName}`;
            
            // Load incidents via AJAX
            fetch(`get_student_incidents.php?student_id=${studentId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('incidentsModalBody').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('incidentsModalBody').innerHTML = `
                        <div class="alert alert-danger">
                            Error loading incidents: ${error}
                        </div>
                    `;
                });
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('incidentsModal'));
            modal.show();
        });
    });

    // View toggle functionality
    const tableViewBtn = document.getElementById('tableViewBtn');
    const gridViewBtn = document.getElementById('gridViewBtn');
    const tableView = document.getElementById('tableView');
    const gridView = document.getElementById('gridView');

    tableViewBtn.addEventListener('click', function() {
        tableView.style.display = 'block';
        gridView.style.display = 'none';
        tableViewBtn.classList.add('active');
        gridViewBtn.classList.remove('active');
    });

    gridViewBtn.addEventListener('click', function() {
        tableView.style.display = 'none';
        gridView.style.display = 'block';
        gridViewBtn.classList.add('active');
        tableViewBtn.classList.remove('active');
    });

    // Auto-submit form when filters change
    const filters = document.querySelectorAll('select[name="course_filter"], select[name="year_filter"], select[name="status_filter"]');
    filters.forEach(filter => {
        filter.addEventListener('change', function() {
            this.form.submit();
        });
    });
});

// Print function
function printStudentList() {
    const printContent = document.getElementById('tableView').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Student List - <?php echo date('Y-m-d'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .badge { padding: 4px 8px; border-radius: 4px; color: white; }
                .bg-success { background-color: #28a745; }
                .bg-warning { background-color: #ffc107; }
                .bg-danger { background-color: #dc3545; }
                .text-center { text-align: center; }
            </style>
        </head>
        <body>
            <h1>Student List</h1>
            <p>Generated on: <?php echo date('F j, Y'); ?></p>
            ${printContent}
        </body>
        </html>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

// Export functions (placeholder - would need backend implementation)
function exportToCSV() {
    alert('CSV export functionality would be implemented here');
    // This would typically make an AJAX call to a backend script
}

function exportToPDF() {
    alert('PDF export functionality would be implemented here');
    // This would typically make an AJAX call to a backend script
}
</script>

<style>
.student-photo, .student-photo-placeholder {
    width: 40px;
    height: 40px;
    object-fit: cover;
}

.table th {
    border-top: none;
    font-weight: 600;
}

.btn-group .btn {
    margin-right: 2px;
}

.badge {
    font-size: 0.75em;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.125);
}

.table-responsive {
    border-radius: 4px;
}

.student-card {
    transition: transform 0.2s;
}

.student-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.card-stats .icon-big {
    font-size: 2em;
}

.card-stats .numbers {
    text-align: right;
}
</style>

<?php 
// Close database connections
if (isset($stmt)) $stmt = null;
if (isset($courses_stmt)) $courses_stmt = null;
if (isset($years_stmt)) $years_stmt = null;
if (isset($stats_stmt)) $stats_stmt = null;
$pdo = null;

include('includes/footer.php'); 
?>