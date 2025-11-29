<?php
session_start();
include('../includes/db_connect.php');

// Check if user is student
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$studentId = $_SESSION['student_id'];

// Define which statuses are visible to students
// Only show "Under Review" and "Third Offense" - NOT "Pending" and NOT "Resolved"
$visibleStatuses = [2, 4]; // Under Review (ID: 2), Third Offense (ID: 4)

// Get student's incidents with joins - only visible statuses
$stmt = $pdo->prepare("
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
");
$stmt->execute([$studentId]);
$incidents = $stmt->fetchAll();

// Get ALL incidents for stats (including pending and resolved)
$stmtAll = $pdo->prepare("
    SELECT status_id, status_name 
    FROM incidents i 
    JOIN incident_statuses ist ON i.status_id = ist.id
    WHERE i.student_id = ?
");
$stmtAll->execute([$studentId]);
$allIncidents = $stmtAll->fetchAll();

// Get student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

// Calculate statistics
$total = count($allIncidents);
$pending = array_filter($allIncidents, fn($i) => $i['status_name'] === 'Pending');
$underReview = array_filter($allIncidents, fn($i) => $i['status_name'] === 'Under Review');
$resolved = array_filter($allIncidents, fn($i) => $i['status_name'] === 'Resolved');
$thirdOffense = array_filter($allIncidents, fn($i) => $i['status_name'] === 'Third Offense');

// Calculate visible incidents count
$visibleCount = count($underReview) + count($thirdOffense);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Violations - Student</title>
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

        .status-under-review {
            background: #e3f2fd;
            color: #1565c0;
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

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            min-width: 120px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .no-violations {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-violations i {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }

        .visibility-notice {
            background: #fff3e0;
            border: 1px solid #ffb74d;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #e65100;
        }

        .stat-hidden {
            opacity: 0.6;
        }

        .stat-hidden .stat-number {
            color: #ffcc80;
        }

        .resolved-badge {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="profile-header">
            <h1>My Active Violations</h1>
            <p><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>
                - <?php echo htmlspecialchars($student['course'] . ' ' . $student['section']); ?></p>

            <div class="visibility-notice">
                <strong>Note:</strong> Only active incidents under review are visible here.
                Pending reports and resolved cases are not shown.
            </div>

            <div class="stats">
                <div class="stat-card <?php echo $total == 0 ? '' : 'stat-hidden'; ?>">
                    <div class="stat-number"><?php echo $total; ?></div>
                    <div>Total Reports</div>
                    <?php if ($total > 0): ?>
                        <small>(<?php echo $visibleCount; ?> active)</small>
                    <?php endif; ?>
                </div>
                <div class="stat-card <?php echo count($pending) > 0 ? 'stat-hidden' : ''; ?>">
                    <div class="stat-number"><?php echo count($pending); ?></div>
                    <div>Pending Review</div>
                    <small>(not visible)</small>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($underReview); ?></div>
                    <div>Under Review</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($thirdOffense); ?></div>
                    <div>Third Offense</div>
                </div>
                <div class="stat-card <?php echo count($resolved) > 0 ? 'stat-hidden' : ''; ?>">
                    <div class="stat-number"><?php echo count($resolved); ?></div>
                    <div>Resolved</div>
                    <small>(not visible)</small>
                </div>
            </div>
        </div>

        <div class="nav">
            <a href="student_dashboard.php">Dashboard</a>
            <a href="student_violations.php" style="color: #764ba2;">My Violations</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="incidents-section">
            <h2 class="section-title">My Active Incidents</h2>
            <p style="color: #666; margin-bottom: 20px;">
                Only incidents that are currently under review by the guidance office are shown below.
                Resolved cases are automatically hidden from view.
            </p>

            <?php if (empty($incidents)): ?>
                <div class="no-violations">
                    <i>🎉</i>
                    <h3>No Active Violations</h3>
                    <p>
                        <?php if ($total > 0): ?>
                            You have <?php echo $total; ?> reported incident(s),
                            but <?php echo count($resolved); ?> are resolved and <?php echo count($pending); ?> are pending
                            review.
                        <?php else: ?>
                            Keep up the good work! You have no reported incidents.
                        <?php endif; ?>
                    </p>
                    <?php if (count($resolved) > 0): ?>
                        <div class="resolved-badge" style="display: inline-block; margin-top: 10px;">
                            ✅ <?php echo count($resolved); ?> case(s) successfully resolved
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($incidents as $incident): ?>
                    <div class="incident-card">
                        <div class="incident-header">
                            <div class="student-info">
                                <strong>Reported by: <?php echo htmlspecialchars($incident['teacher_name']); ?></strong>
                                <span class="incident-type"><?php echo htmlspecialchars($incident['type_name']); ?></span>
                            </div>
                            <div class="status-badges">
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
                            <?php if ($incident['updated_at'] && $incident['updated_at'] != $incident['created_at']): ?>
                                | Last Updated: <?php echo date('M j, Y g:i A', strtotime($incident['updated_at'])); ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($incident['recommendation']): ?>
                            <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <strong>Guidance Counselor Recommendation:</strong>
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