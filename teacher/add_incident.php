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

try {
    // Fetch students for dropdown
    $students_query = "SELECT id, firstname, middlename, lastname, course, year, section 
                      FROM students 
                      WHERE deleted = 0 
                      ORDER BY firstname, lastname";
    $students_stmt = $pdo->prepare($students_query);
    $students_stmt->execute();
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch incident types - using type_name column
    $incident_types_query = "SELECT id, type_name FROM incident_types ORDER BY type_name";
    $incident_types_stmt = $pdo->prepare($incident_types_query);
    $incident_types_stmt->execute();
    $incident_types = $incident_types_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch incident statuses - table name is incident_statuses (plural)
    $incident_status_query = "SELECT id, status_name FROM incident_statuses ORDER BY id";
    $incident_status_stmt = $pdo->prepare($incident_status_query);
    $incident_status_stmt->execute();
    $incident_statuses = $incident_status_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_POST) {
    $student_id = $_POST['student_id'] ?? '';
    $incident_type_id = $_POST['incident_type_id'] ?? '';
    $description = $_POST['description'] ?? '';
    $status_id = $_POST['status_id'] ?? 1; // Default to first status

    // Validation
    if (empty($student_id) || empty($incident_type_id) || empty($description)) {
        $error = "Please fill in all required fields (Student, Incident Type, and Description).";
    } else {
        try {
            // Insert incident - using exact column names from your database
            $insert_query = "INSERT INTO incidents (student_id, incident_type_id, description, status_id) 
                            VALUES (:student_id, :incident_type_id, :description, :status_id)";
            
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->bindParam(':student_id', $student_id);
            $insert_stmt->bindParam(':incident_type_id', $incident_type_id);
            $insert_stmt->bindParam(':description', $description);
            $insert_stmt->bindParam(':status_id', $status_id);
            
            if ($insert_stmt->execute()) {
                $success = "Incident reported successfully!";
                // Clear form fields
                $_POST = array();
            } else {
                $error = "Failed to report incident. Please try again.";
            }
            
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
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
                                <h4 class="card-title">Report New Incident</h4>
                                <p class="card-category">Document student behavioral or academic incidents</p>
                            </div>
                            <div class="col-md-6 text-end">
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

                        <!-- Incident Form -->
                        <form method="POST" action="">
                            <div class="row">
                                <!-- Student Selection -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="student_id" class="form-label required">Select Student</label>
                                        <select class="form-select" id="student_id" name="student_id" required>
                                            <option value="">Choose a student...</option>
                                            <?php foreach ($students as $student): ?>
                                                <option value="<?php echo $student['id']; ?>" 
                                                    <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($student['firstname'] . ' ' . ($student['middlename'] ? $student['middlename'] . ' ' : '') . $student['lastname']); ?>
                                                    - <?php echo htmlspecialchars($student['course'] ?? 'No Course'); ?>
                                                    (Year <?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Select the student involved in the incident</div>
                                    </div>
                                </div>

                                <!-- Incident Type -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="incident_type_id" class="form-label required">Incident Type</label>
                                        <select class="form-select" id="incident_type_id" name="incident_type_id" required>
                                            <option value="">Select incident type...</option>
                                            <?php foreach ($incident_types as $type): ?>
                                                <option value="<?php echo $type['id']; ?>"
                                                    <?php echo (isset($_POST['incident_type_id']) && $_POST['incident_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Choose the category that best describes this incident</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Status -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status_id" class="form-label">Status</label>
                                        <select class="form-select" id="status_id" name="status_id">
                                            <?php foreach ($incident_statuses as $status): ?>
                                                <option value="<?php echo $status['id']; ?>"
                                                    <?php echo (isset($_POST['status_id']) && $_POST['status_id'] == $status['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($status['status_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Current status of this incident</div>
                                    </div>
                                </div>

                                <!-- Date of Incident -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="incident_date" class="form-label">Date of Incident</label>
                                        <input type="date" class="form-control" id="incident_date" name="incident_date" 
                                               value="<?php echo isset($_POST['incident_date']) ? $_POST['incident_date'] : date('Y-m-d'); ?>">
                                        <div class="form-text">When did this incident occur?</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Incident Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label required">Incident Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" 
                                          placeholder="Provide a detailed description of the incident..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="form-text">
                                    Be specific about what happened, when, where, and who was involved. Include any relevant details.
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Report Incident
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo"></i> Reset Form
                                    </button>
                                    <a href="teacher_dashboard.php" class="btn btn-link text-muted">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Student Reference -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-info">
                        <h5 class="card-title">Student Quick Reference</h5>
                        <p class="card-category">Recent students with incidents</p>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $recent_students_query = "SELECT s.id, s.firstname, s.lastname, s.course, s.year,
                                                     COUNT(i.id) as incident_count
                                                     FROM students s 
                                                     LEFT JOIN incidents i ON s.id = i.student_id 
                                                     WHERE s.deleted = 0 
                                                     GROUP BY s.id 
                                                     ORDER BY incident_count DESC, s.firstname 
                                                     LIMIT 8";
                            $recent_students_stmt = $pdo->prepare($recent_students_query);
                            $recent_students_stmt->execute();
                            $recent_students = $recent_students_stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            $recent_students = [];
                        }
                        ?>

                        <div class="row">
                            <?php foreach ($recent_students as $student): ?>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="card student-card" 
                                         onclick="document.getElementById('student_id').value='<?php echo $student['id']; ?>'"
                                         style="cursor: pointer;">
                                        <div class="card-body text-center p-3">
                                            <h6 class="card-title mb-1">
                                                <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>
                                            </h6>
                                            <p class="card-text text-muted small mb-1">
                                                <?php echo htmlspecialchars($student['course'] ?? 'No Course'); ?>
                                            </p>
                                            <span class="badge bg-<?php echo $student['incident_count'] > 0 ? 'warning' : 'success'; ?>">
                                                <?php echo $student['incident_count']; ?> incidents
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Enhanced Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-set today's date for incident date
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('incident_date').value = today;

    // Student card click handler
    const studentCards = document.querySelectorAll('.student-card');
    studentCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove active class from all cards
            studentCards.forEach(c => c.classList.remove('active'));
            // Add active class to clicked card
            this.classList.add('active');
        });
    });

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const studentId = document.getElementById('student_id').value;
        const incidentType = document.getElementById('incident_type_id').value;
        const description = document.getElementById('description').value;

        if (!studentId || !incidentType || !description.trim()) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
    });

    // Character counter for description
    const descriptionTextarea = document.getElementById('description');
    const descriptionCounter = document.createElement('div');
    descriptionCounter.className = 'form-text text-end';
    descriptionTextarea.parentNode.appendChild(descriptionCounter);

    descriptionTextarea.addEventListener('input', function() {
        const count = this.value.length;
        descriptionCounter.textContent = `${count} characters`;
        
        if (count < 50) {
            descriptionCounter.className = 'form-text text-end text-warning';
        } else if (count > 1000) {
            descriptionCounter.className = 'form-text text-end text-danger';
        } else {
            descriptionCounter.className = 'form-text text-end text-success';
        }
    });

    // Trigger initial count
    descriptionTextarea.dispatchEvent(new Event('input'));
});
</script>

<style>
.required::after {
    content: " *";
    color: #dc3545;
}

.student-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.student-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.student-card.active {
    border-color: #28a745;
    background-color: #f8fff9;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.125);
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}

.form-text {
    font-size: 0.875rem;
}
</style>

<?php 
// Close database connections
if (isset($students_stmt)) $students_stmt = null;
if (isset($incident_types_stmt)) $incident_types_stmt = null;
if (isset($incident_status_stmt)) $incident_status_stmt = null;
if (isset($recent_students_stmt)) $recent_students_stmt = null;
if (isset($insert_stmt)) $insert_stmt = null;
$pdo = null;

include('includes/footer.php'); 
?>