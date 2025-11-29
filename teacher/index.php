<?php
// Start session at the very top with no whitespace before
session_start();

// Check if user is logged in and is a teacher BEFORE any output
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit();
}

// Include database connection first
include('../includes/db_connect.php');

// Get teacher ID from session
$teacherId = $_SESSION['user_id'];

// Initialize variables
$errors = [];
$success = '';

// Get incident types and statuses for the report section
$stmt = $pdo->query("SELECT * FROM incident_types ORDER BY type_name");
$incidentTypes = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM incident_statuses ORDER BY id");
$incidentStatuses = $stmt->fetchAll();

// Get all students for the dropdown
$students_query = "SELECT id, username, firstname, lastname, course, section FROM students WHERE deleted = 0 ORDER BY firstname, lastname";
$students_stmt = $pdo->prepare($students_query);
$students_stmt->execute();
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// Use Pending status (ID: 1) for new incidents
$pendingStatusId = 1;

// Handle incident report form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_username'])) {
    $errors = [];

    $studentUsername = trim($_POST['student_username'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $incidentDate = $_POST['incident_date'] ?? '';
    $incidentTypeId = $_POST['incident_type_id'] ?? '';

    // Validate inputs
    if (empty($studentUsername)) {
        $errors[] = "Student username is required.";
    }

    if (empty($title)) {
        $errors[] = "Incident title is required.";
    }

    if (empty($description)) {
        $errors[] = "Incident description is required.";
    }

    if (empty($incidentDate)) {
        $errors[] = "Incident date is required.";
    }

    if (empty($incidentTypeId)) {
        $errors[] = "Incident type is required.";
    }

    // Check if student exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE username = ? AND deleted = 0");
        $stmt->execute([$studentUsername]);
        $student = $stmt->fetch();

        if (!$student) {
            $errors[] = "Student not found with username: " . htmlspecialchars($studentUsername);
        } else {
            $studentId = $student['id'];
        }
    }

    // Insert incident
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO incidents (
                    student_id, teacher_id, title, description, incident_date, 
                    severity, incident_type_id, status_id
                ) VALUES (?, ?, ?, ?, ?, 'Medium', ?, ?)
            ");

            $stmt->execute([
                $studentId,
                $teacherId,
                $title,
                $description,
                $incidentDate,
                $incidentTypeId,
                $pendingStatusId
            ]);

            $_SESSION['success'] = "Incident reported successfully!";
            header("Location: index.php");
            exit();

        } catch (PDOException $e) {
            $errors[] = "Failed to report incident: " . $e->getMessage();
        }
    }
}

// Initialize dashboard statistics variables
$total_students_count = 0;
$total_incidents_count = 0;
$teacher_incidents_count = 0;
$pending_incidents_count = 0;
$teacher_incidents = [];
$recent_incidents = [];
$incident_stats = [];
$monthly_trends = [];

try {
    // Total students count
    $total_students_query = "SELECT COUNT(*) as count FROM students WHERE deleted = 0";
    $total_students_stmt = $pdo->prepare($total_students_query);
    $total_students_stmt->execute();
    $total_students_count = $total_students_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Total incidents count
    $total_incidents_query = "SELECT COUNT(*) as count FROM incidents";
    $total_incidents_stmt = $pdo->prepare($total_incidents_query);
    $total_incidents_stmt->execute();
    $total_incidents_count = $total_incidents_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Teacher's incidents count
    $teacher_incidents_query = "SELECT COUNT(*) as count FROM incidents WHERE teacher_id = ?";
    $teacher_incidents_stmt = $pdo->prepare($teacher_incidents_query);
    $teacher_incidents_stmt->execute([$teacherId]);
    $teacher_incidents_count = $teacher_incidents_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Pending incidents count
    $pending_incidents_query = "SELECT COUNT(*) as count FROM incidents i 
                               WHERE i.status_id IN (SELECT id FROM incident_statuses WHERE status_name = 'Pending')";
    $pending_incidents_stmt = $pdo->prepare($pending_incidents_query);
    $pending_incidents_stmt->execute();
    $pending_incidents_count = $pending_incidents_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Get teacher's reported incidents with joins
    $teacher_incidents_query = "
        SELECT 
            i.*, 
            s.firstname, s.lastname, s.course, s.section,
            it.type_name,
            ist.status_name,
            u.username as teacher_username
        FROM incidents i 
        JOIN students s ON i.student_id = s.id 
        JOIN incident_types it ON i.incident_type_id = it.id
        JOIN incident_statuses ist ON i.status_id = ist.id
        JOIN users u ON i.teacher_id = u.id
        WHERE i.teacher_id = ? 
        ORDER BY i.created_at DESC
        LIMIT 10
    ";
    $teacher_incidents_stmt = $pdo->prepare($teacher_incidents_query);
    $teacher_incidents_stmt->execute([$teacherId]);
    $teacher_incidents = $teacher_incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent school incidents for the chart (last 6 months)
    $recent_incidents_query = "SELECT i.*, 
                              it.type_name as incident_type,
                              s.status_name as status,
                              st.firstname, st.lastname, st.course, st.year
                              FROM incidents i 
                              LEFT JOIN incident_types it ON i.incident_type_id = it.id 
                              LEFT JOIN incident_statuses s ON i.status_id = s.id 
                              LEFT JOIN students st ON i.student_id = st.id 
                              ORDER BY i.created_at DESC 
                              LIMIT 5";
    $recent_incidents_stmt = $pdo->prepare($recent_incidents_query);
    $recent_incidents_stmt->execute();
    $recent_incidents = $recent_incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Incident statistics by type for chart
    $incident_stats_query = "SELECT it.type_name as incident_type, COUNT(*) as count 
                            FROM incidents i 
                            LEFT JOIN incident_types it ON i.incident_type_id = it.id 
                            GROUP BY it.type_name 
                            ORDER BY count DESC";
    $incident_stats_stmt = $pdo->prepare($incident_stats_query);
    $incident_stats_stmt->execute();
    $incident_stats = $incident_stats_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    error_log("Database error in teacher dashboard: " . $e->getMessage());
    $errors[] = "A system error occurred. Please try again later.";
}

// Now include header after all processing is done
include('includes/header.php');
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

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
                <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">people</i>
                        </div>
                        <p class="card-category">Total Students</p>
                        <h3 class="card-title">
                           <?php echo htmlspecialchars($total_students_count); ?>
                        </h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">school</i> All enrolled students
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
                        <p class="card-category">My Reports</p>
                        <h3 class="card-title">
                           <?php echo htmlspecialchars($teacher_incidents_count); ?>
                        </h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">list_alt</i> Total incidents reported
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">schedule</i>
                        </div>
                        <p class="card-category">Pending Reviews</p>
                        <h3 class="card-title">
                           <?php echo htmlspecialchars($pending_incidents_count); ?>
                        </h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">update</i> Awaiting guidance review
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
                        <p class="card-category">Cases Resolved</p>
                        <h3 class="card-title">
                           <?php echo htmlspecialchars(max(0, $total_incidents_count - $pending_incidents_count)); ?>
                        </h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">done_all</i> Successfully resolved
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Report Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-danger">
                        <h4 class="card-title">
                            <i class="material-icons">report_problem</i>
                            Quick Incident Report
                        </h4>
                        <p class="card-category">Report a new student incident</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="incidentForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="student_search" class="bmd-label-floating"></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="material-icons">search</i>
                                                </span>
                                            </div>
                                            <input type="text" class="form-control" id="student_search" 
                                                   placeholder="Type to search students by name, username, course, or section..."
                                                   autocomplete="off">
                                        </div>
                                        <input type="hidden" id="student_username" name="student_username" required>
                                        <small class="form-text text-muted">Start typing to search for students. Select from dropdown.</small>
                                        <div id="student_search_results" class="search-results-dropdown"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title" class="bmd-label-floating">Incident Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="incident_type_id"></label>
                                        <select class="form-control" id="incident_type_id" name="incident_type_id" required>
                                            <option value="">Incident Type</option>
                                            <?php foreach ($incidentTypes as $type): ?>
                                                <option value="<?php echo $type['id']; ?>" 
                                                    <?php echo (isset($_POST['incident_type_id']) && $_POST['incident_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="incident_date">Incident Date *</label>
                                        <input type="date" class="form-control" id="incident_date" name="incident_date" 
                                               value="<?php echo htmlspecialchars($_POST['incident_date'] ?? date('Y-m-d')); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description" class="bmd-label-floating">Incident Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="" 
                                          required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <small class="form-text text-muted" id="charCount">0/1000 characters</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-danger" id="submitBtn">
                                        <i class="material-icons">send</i> Submit Report
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="clearForm()">
                                        <i class="material-icons">clear</i> Clear Form
                                    </button>
                                    <small class="form-text text-muted ml-2" style="display: inline-block;">
                                        * All fields are required
                                    </small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Recent Reports -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">
                            <i class="material-icons">list_alt</i>
                            My Recent Incident Reports
                        </h4>
                        <p class="card-category">Latest incidents reported by you</p>
                    </div>
                    <div class="card-body">
                        <?php if (count($teacher_incidents) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="text-warning">
                                        <tr>
                                            <th>Student</th>
                                            <th>Course/Section</th>
                                            <th>Incident Details</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Date Reported</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teacher_incidents as $incident): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($incident['firstname'] . ' ' . $incident['lastname']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($incident['teacher_username']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($incident['course'] ?? 'N/A'); ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($incident['section'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <strong class="text-primary"><?php echo htmlspecialchars($incident['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted" data-toggle="tooltip" title="<?php echo htmlspecialchars($incident['description']); ?>">
                                                        <?php
                                                        $description = $incident['description'];
                                                        echo strlen($description) > 60 ? substr($description, 0, 60) . '...' : $description;
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-pill badge-secondary">
                                                        <?php echo htmlspecialchars($incident['type_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-pill badge-<?php
                                                    switch ($incident['status_name']) {
                                                        case 'Resolved': echo 'success'; break;
                                                        case 'Under Review': echo 'warning'; break;
                                                        case 'Pending': echo 'info'; break;
                                                        case 'Third Offense': echo 'danger'; break;
                                                        default: echo 'secondary';
                                                    }
                                                    ?>">
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
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="material-icons text-muted" style="font-size: 64px;">assignment</i>
                                <h4 class="text-muted">No incidents reported yet</h4>
                                <p class="text-muted">Start by reporting your first incident using the form above.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- JavaScript for Enhanced Student Search and Form Handling -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Student data from PHP
const studentsData = <?php echo json_encode($students); ?>;

document.addEventListener('DOMContentLoaded', function () {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Character counter for description
    const description = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    
    if (description && charCount) {
        description.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = `${length}/1000 characters`;
            
            if (length > 1000) {
                charCount.className = 'form-text text-danger';
            } else if (length > 800) {
                charCount.className = 'form-text text-warning';
            } else {
                charCount.className = 'form-text text-muted';
            }
        });
        
        // Trigger initial count
        description.dispatchEvent(new Event('input'));
    }

    // Enhanced student search functionality
    const studentSearch = document.getElementById('student_search');
    const studentUsername = document.getElementById('student_username');
    const searchResults = document.getElementById('student_search_results');
    let searchTimeout = null;

    if (studentSearch && searchResults) {
        // Focus on search input when page loads
        studentSearch.focus();

        // Search input event listener
        studentSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            if (searchTerm.length < 2) {
                hideSearchResults();
                return;
            }

            searchTimeout = setTimeout(() => {
                performStudentSearch(searchTerm);
            }, 300);
        });

        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!studentSearch.contains(e.target) && !searchResults.contains(e.target)) {
                hideSearchResults();
            }
        });

        // Keyboard navigation
        studentSearch.addEventListener('keydown', function(e) {
            const visibleResults = searchResults.querySelectorAll('.search-result-item:not(.d-none)');
            
            if (e.key === 'ArrowDown' && visibleResults.length > 0) {
                e.preventDefault();
                visibleResults[0].focus();
            } else if (e.key === 'Escape') {
                hideSearchResults();
            }
        });
    }

    // Form validation
    const incidentForm = document.getElementById('incidentForm');
    if (incidentForm) {
        incidentForm.addEventListener('submit', function(e) {
            const studentSelected = studentUsername.value.trim();
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if (!studentSelected) {
                e.preventDefault();
                showAlert('Please select a student from the search results.', 'danger');
                studentSearch.focus();
                return;
            }
            
            if (description.length > 1000) {
                e.preventDefault();
                showAlert('Description must be 1000 characters or less.', 'danger');
                return;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="material-icons spin">refresh</i> Submitting...';
            submitBtn.disabled = true;
        });
    }
});

// Perform student search
function performStudentSearch(searchTerm) {
    const searchResults = document.getElementById('student_search_results');
    const searchLower = searchTerm.toLowerCase();
    
    // Filter students based on search term
    const filteredStudents = studentsData.filter(student => {
        const fullName = `${student.firstname} ${student.lastname}`.toLowerCase();
        const username = student.username.toLowerCase();
        const course = (student.course || '').toLowerCase();
        const section = (student.section || '').toLowerCase();
        
        return fullName.includes(searchLower) || 
               username.includes(searchLower) ||
               course.includes(searchLower) ||
               section.includes(searchLower);
    });

    displaySearchResults(filteredStudents, searchTerm);
}

// Display search results
function displaySearchResults(students, searchTerm) {
    const searchResults = document.getElementById('student_search_results');
    
    if (students.length === 0) {
        searchResults.innerHTML = `
            <div class="search-result-item text-muted p-3">
                <i class="material-icons mr-2">search_off</i>
                No students found matching "${searchTerm}"
            </div>
        `;
        searchResults.classList.remove('d-none');
        return;
    }

    let resultsHTML = '';
    
    students.slice(0, 10).forEach(student => {
        const fullName = `${student.firstname} ${student.lastname}`;
        const displayText = `${fullName} (${student.username}) - ${student.course || 'No Course'} ${student.section || ''}`;
        
        resultsHTML += `
            <div class="search-result-item" 
                 tabindex="0"
                 data-username="${student.username}"
                 data-display="${displayText}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${fullName}</strong>
                        <div class="text-muted small">
                            <span class="badge badge-light">${student.username}</span>
                            <span class="badge badge-info">${student.course || 'No Course'}</span>
                            <span class="badge badge-secondary">${student.section || 'No Section'}</span>
                        </div>
                    </div>
                    <i class="material-icons text-success">person_add</i>
                </div>
            </div>
        `;
    });

    searchResults.innerHTML = resultsHTML;
    searchResults.classList.remove('d-none');

    // Add click event listeners to results
    const resultItems = searchResults.querySelectorAll('.search-result-item');
    resultItems.forEach(item => {
        item.addEventListener('click', function() {
            selectStudent(this);
        });
        
        item.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                selectStudent(this);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                const next = this.nextElementSibling;
                if (next && next.classList.contains('search-result-item')) {
                    next.focus();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prev = this.previousElementSibling;
                if (prev && prev.classList.contains('search-result-item')) {
                    prev.focus();
                } else {
                    document.getElementById('student_search').focus();
                }
            } else if (e.key === 'Escape') {
                hideSearchResults();
            }
        });
    });

    // Focus on first result for keyboard navigation
    if (resultItems.length > 0) {
        resultItems[0].focus();
    }
}

// Select a student from search results
function selectStudent(element) {
    const username = element.getAttribute('data-username');
    const displayText = element.getAttribute('data-display');
    
    document.getElementById('student_username').value = username;
    document.getElementById('student_search').value = displayText;
    
    hideSearchResults();
    
    // Show success feedback
    element.classList.add('selected');
    setTimeout(() => {
        element.classList.remove('selected');
    }, 1000);
    
    // Move focus to next field
    document.getElementById('title').focus();
}

// Hide search results
function hideSearchResults() {
    const searchResults = document.getElementById('student_search_results');
    searchResults.classList.add('d-none');
}

// Clear form function
function clearForm() {
    document.getElementById('incidentForm').reset();
    document.getElementById('student_username').value = '';
    document.getElementById('charCount').textContent = '0/1000 characters';
    document.getElementById('charCount').className = 'form-text text-muted';
    hideSearchResults();
    
    // Focus on search input
    document.getElementById('student_search').focus();
    
    showAlert('Form cleared successfully. You can start a new report.', 'info');
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alertDiv.innerHTML = `
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <i class="material-icons">close</i>
        </button>
        <span>${message}</span>
    `;
    
    const cardBody = document.querySelector('.card-body');
    const existingAlert = cardBody.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    cardBody.insertBefore(alertDiv, document.getElementById('incidentForm'));
    
    // Auto-hide the alert after 3 seconds
    setTimeout(() => {
        $(alertDiv).fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}

// Add spin animation for material icons
const style = document.createElement('style');
style.textContent = `
    .material-icons.spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .search-results-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 4px 4px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .search-result-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .search-result-item:hover,
    .search-result-item:focus {
        background-color: #f8f9fa;
        outline: none;
    }
    .search-result-item.selected {
        background-color: #e3f2fd;
    }
    .search-result-item:last-child {
        border-bottom: none;
    }
`;
document.head.appendChild(style);
</script>

<style>
.search-results-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.search-result-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-result-item:hover,
.search-result-item:focus {
    background-color: #f8f9fa;
    outline: none;
}

.search-result-item.selected {
    background-color: #e3f2fd;
}

.search-result-item:last-child {
    border-bottom: none;
}

.form-group {
    position: relative;
}

.material-icons.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.badge {
    font-size: 0.7em;
    margin-right: 4px;
}
</style>

<?php
// Close connection
$pdo = null;
include('includes/footer.php');
?>