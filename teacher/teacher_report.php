<?php
session_start();
include('../includes/db_connect.php');

// Check if user is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

// Get incident types and statuses
$stmt = $pdo->query("SELECT * FROM incident_types ORDER BY type_name");
$incidentTypes = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM incident_statuses ORDER BY id");
$incidentStatuses = $stmt->fetchAll();

// Use Pending status (ID: 1) for new incidents
$pendingStatusId = 1;

$teacherId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    $studentUsername = trim($_POST['student_username'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $incidentDate = $_POST['incident_date'] ?? '';
    $severity = $_POST['severity'] ?? 'Low';
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
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $studentId,
                $teacherId,
                $title,
                $description,
                $incidentDate,
                $severity,
                $incidentTypeId,
                $pendingStatusId
            ]);

            $success = "Incident reported successfully!";

            // Clear form
            $studentUsername = $title = $description = $incidentDate = '';
            $severity = 'Low';
            $incidentTypeId = '';

        } catch (PDOException $e) {
            $errors[] = "Failed to report incident: " . $e->getMessage();
        }
    }
}

// Get teacher's reported incidents with joins
$stmt = $pdo->prepare("
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
");
$stmt->execute([$teacherId]);
$incidents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident - Teacher</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .header h1 {
            margin-bottom: 10px;
        }

        .nav {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .nav a {
            color: #667eea;
            text-decoration: none;
            margin-right: 20px;
            font-weight: 600;
        }

        .form-section,
        .incidents-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }

        .success {
            background: #efe;
            color: #363;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #363;
        }

        .incident-card {
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .incident-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .student-info {
            font-weight: 600;
            color: #333;
        }

        .incident-date {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }

        .severity {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 5px;
        }

        .severity-low {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .severity-medium {
            background: #fff3e0;
            color: #ef6c00;
        }

        .severity-high {
            background: #ffebee;
            color: #c62828;
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 5px;
        }

        .status-pending {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-under-review {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-resolved {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-third-offense {
            background: #fce4ec;
            color: #ad1457;
        }

        .incident-type {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 5px;
        }

        .status-badges {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Report Student Incident</h1>
            <p>Teacher Portal - Student Affairs and Development System</p>
        </div>

        <div class="nav">
            <a href="teacher_dashboard.php">Dashboard</a>
            <a href="teacher_report.php" style="color: #764ba2;">Report Incident</a>
            <a href="logout.php">Logout</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="success">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <h2 class="section-title">Report New Incident</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="student_username">Student Username *</label>
                    <input type="text" id="student_username" name="student_username"
                        value="<?php echo htmlspecialchars($studentUsername ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="title">Incident Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="incident_type_id">Incident Type *</label>
                    <select id="incident_type_id" name="incident_type_id" required>
                        <option value="">Select Incident Type</option>
                        <?php foreach ($incidentTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo ($incidentTypeId ?? '') == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Incident Description *</label>
                    <textarea id="description" name="description"
                        required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="incident_date">Incident Date *</label>
                    <input type="date" id="incident_date" name="incident_date"
                        value="<?php echo htmlspecialchars($incidentDate ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="severity">Severity Level</label>
                    <select id="severity" name="severity">
                        <option value="Low" <?php echo ($severity ?? 'Low') === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($severity ?? 'Low') === 'Medium' ? 'selected' : ''; ?>>Medium
                        </option>
                        <option value="High" <?php echo ($severity ?? 'Low') === 'High' ? 'selected' : ''; ?>>High
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn">Submit Report</button>
            </form>
        </div>

        <div class="incidents-section">
            <h2 class="section-title">My Reported Incidents</h2>

            <?php if (empty($incidents)): ?>
                <p>No incidents reported yet.</p>
            <?php else: ?>
                <?php foreach ($incidents as $incident): ?>
                    <div class="incident-card">
                        <div class="incident-header">
                            <div class="student-info">
                                <?php echo htmlspecialchars($incident['firstname'] . ' ' . $incident['lastname']); ?>
                                (<?php echo htmlspecialchars($incident['course'] . ' - ' . $incident['section']); ?>)
                            </div>
                            <div class="status-badges">
                                <span class="incident-type">
                                    <?php echo htmlspecialchars($incident['type_name']); ?>
                                </span>
                                <span class="severity severity-<?php echo strtolower($incident['severity']); ?>">
                                    <?php echo $incident['severity']; ?>
                                </span>
                                <span
                                    class="status status-<?php echo strtolower(str_replace(' ', '-', $incident['status_name'])); ?>">
                                    <?php echo htmlspecialchars($incident['status_name']); ?>
                                </span>
                            </div>
                        </div>

                        <h3><?php echo htmlspecialchars($incident['title']); ?></h3>
                        <p><?php echo htmlspecialchars($incident['description']); ?></p>

                        <div class="incident-date">
                            Incident Date: <?php echo date('M j, Y', strtotime($incident['incident_date'])); ?>
                            | Reported: <?php echo date('M j, Y g:i A', strtotime($incident['created_at'])); ?>
                        </div>

                        <?php if ($incident['recommendation']): ?>
                            <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <strong>Guidance Recommendation:</strong>
                                <?php echo htmlspecialchars($incident['recommendation']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>