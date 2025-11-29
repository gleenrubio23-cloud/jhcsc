<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

include '../includes/db_connect.php';

$admin_name = $_SESSION['username'];
$error = '';
$success = '';

// Initialize report data variables
$report_data = [];
$filtered_data = [];
$chart_data = [];

// Default date range (last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'incident_summary';
$course_filter = isset($_GET['course']) ? $_GET['course'] : '';

try {
    // Get available courses for filter
    $courses = $pdo->query("SELECT DISTINCT course FROM students WHERE course IS NOT NULL AND course != '' AND deleted = 0 ORDER BY course")->fetchAll();

    // Generate reports based on type and filters
    switch ($report_type) {
        case 'incident_summary':
            $sql = "SELECT 
                    it.type_name as incident_type,
                    COUNT(*) as total_incidents,
                    SUM(CASE WHEN ist.status_name = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN ist.status_name = 'Under Review' THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN ist.status_name = 'Resolved' THEN 1 ELSE 0 END) as resolved
                FROM incidents i
                JOIN incident_types it ON i.incident_type_id = it.id
                JOIN incident_statuses ist ON i.status_id = ist.id
                WHERE i.created_at BETWEEN ? AND ?
                " . ($course_filter ? " AND i.student_id IN (SELECT id FROM students WHERE course = ?)" : "") . "
                GROUP BY it.type_name
                ORDER BY total_incidents DESC";
            
            $stmt = $pdo->prepare($sql);
            if ($course_filter) {
                $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59', $course_filter]);
            } else {
                $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }
            $report_data = $stmt->fetchAll();
            break;

        case 'student_offenses':
            $sql = "SELECT 
                    s.id,
                    CONCAT(s.firstname, ' ', s.lastname) as student_name,
                    s.course,
                    COUNT(i.id) as total_offenses,
                    GROUP_CONCAT(DISTINCT it.type_name) as offense_types
                FROM students s
                LEFT JOIN incidents i ON s.id = i.student_id
                LEFT JOIN incident_types it ON i.incident_type_id = it.id
                WHERE s.deleted = 0
                AND (i.created_at BETWEEN ? AND ? OR i.id IS NULL)
                " . ($course_filter ? " AND s.course = ?" : "") . "
                GROUP BY s.id
                HAVING total_offenses > 0
                ORDER BY total_offenses DESC";
            
            $stmt = $pdo->prepare($sql);
            if ($course_filter) {
                $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59', $course_filter]);
            } else {
                $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }
            $report_data = $stmt->fetchAll();
            break;

        case 'monthly_trends':
            $sql = "SELECT 
                    DATE_FORMAT(i.created_at, '%Y-%m') as month,
                    COUNT(*) as total_incidents,
                    it.type_name,
                    COUNT(*) as type_count
                FROM incidents i
                JOIN incident_types it ON i.incident_type_id = it.id
                WHERE i.created_at BETWEEN ? AND ?
                " . ($course_filter ? " AND i.student_id IN (SELECT id FROM students WHERE course = ?)" : "") . "
                GROUP BY DATE_FORMAT(i.created_at, '%Y-%m'), it.type_name
                ORDER BY month, type_count DESC";
            
            $stmt = $pdo->prepare($sql);
            if ($course_filter) {
                $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59', $course_filter]);
            } else {
                $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }
            $raw_data = $stmt->fetchAll();
            
            // Process data for chart
            $months = [];
            $types = [];
            $chart_data = [];
            
            foreach ($raw_data as $row) {
                if (!in_array($row['month'], $months)) {
                    $months[] = $row['month'];
                }
                if (!in_array($row['type_name'], $types)) {
                    $types[] = $row['type_name'];
                }
            }
            
            foreach ($types as $type) {
                $type_data = [
                    'label' => $type,
                    'data' => []
                ];
                foreach ($months as $month) {
                    $count = 0;
                    foreach ($raw_data as $row) {
                        if ($row['month'] == $month && $row['type_name'] == $type) {
                            $count = $row['type_count'];
                            break;
                        }
                    }
                    $type_data['data'][] = $count;
                }
                $chart_data[] = $type_data;
            }
            
            $report_data = $raw_data;
            break;

        case 'course_analysis':
            $sql = "SELECT 
                    s.course,
                    COUNT(i.id) as total_incidents,
                    COUNT(DISTINCT s.id) as students_involved,
                    COUNT(i.id) / COUNT(DISTINCT s.id) as incidents_per_student,
                    it.type_name,
                    COUNT(CASE WHEN it.type_name = it.type_name THEN 1 END) as type_count
                FROM students s
                LEFT JOIN incidents i ON s.id = i.student_id
                LEFT JOIN incident_types it ON i.incident_type_id = it.id
                WHERE s.deleted = 0 
                AND s.course IS NOT NULL 
                AND s.course != ''
                AND (i.created_at BETWEEN ? AND ? OR i.id IS NULL)
                " . ($course_filter ? " AND s.course = ?" : "") . "
                GROUP BY s.course, it.type_name
                HAVING total_incidents > 0
                ORDER BY total_incidents DESC";
            
            $stmt = $pdo->prepare($sql);
            if ($course_filter) {
                $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59', $course_filter]);
            } else {
                $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }
            $report_data = $stmt->fetchAll();
            break;
    }

    // Get summary statistics for the header
    $total_incidents = $pdo->query("SELECT COUNT(*) FROM incidents WHERE created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'")->fetchColumn();
    $pending_cases = $pdo->query("SELECT COUNT(*) FROM incidents i JOIN incident_statuses s ON i.status_id = s.id WHERE s.status_name = 'Pending' AND i.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'")->fetchColumn();
    $resolved_cases = $pdo->query("SELECT COUNT(*) FROM incidents i JOIN incident_statuses s ON i.status_id = s.id WHERE s.status_name = 'Resolved' AND i.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'")->fetchColumn();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Include header
include('includes/header.php');
?>

<!-- Reports Content -->
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

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="material-icons">check_circle</i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title">Reports & Analytics</h4>
                        <p class="card-category">Generate detailed reports and analyze incident data</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Report Filters</h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="reports.php" class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="report_type"> </label>
                                    <select class="form-control" id="report_type" name="report_type">
                                        <option value="incident_summary" <?php echo $report_type == 'incident_summary' ? 'selected' : ''; ?>>Incident Summary</option>
                                        <option value="student_offenses" <?php echo $report_type == 'student_offenses' ? 'selected' : ''; ?>>Student Offenses</option>
                                        <option value="monthly_trends" <?php echo $report_type == 'monthly_trends' ? 'selected' : ''; ?>>Monthly Trends</option>
                                        <option value="course_analysis" <?php echo $report_type == 'course_analysis' ? 'selected' : ''; ?>>Course Analysis</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                   <label for="course"> </label>
                                    <select class="form-control" id="course" name="course">
                                        <option value="">All Courses</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo htmlspecialchars($course['course']); ?>" 
                                                <?php echo $course_filter == $course['course'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['course']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Generate Report</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">assessment</i>
                        </div>
                        <p class="card-category">Total Incidents</p>
                        <h3 class="card-title"><?php echo $total_incidents; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">date_range</i>
                            <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>
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
                        <p class="card-category">Pending Cases</p>
                        <h3 class="card-title"><?php echo $pending_cases; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">warning</i>
                            Needs attention
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">check_circle</i>
                        </div>
                        <p class="card-category">Resolved Cases</p>
                        <h3 class="card-title"><?php echo $resolved_cases; ?></h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">done_all</i>
                            Successfully closed
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card card-stats">
                    <div class="card-header card-header-primary card-header-icon">
                        <div class="card-icon">
                            <i class="material-icons">trending_up</i>
                        </div>
                        <p class="card-category">Report Period</p>
                        <h3 class="card-title"><?php echo ceil((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24)); ?> days</h3>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="material-icons">calendar_today</i>
                            Analysis period
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Results -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title">
                            <?php 
                            $report_titles = [
                                'incident_summary' => 'Incident Summary Report',
                                'student_offenses' => 'Student Offenses Report',
                                'monthly_trends' => 'Monthly Trends Analysis',
                                'course_analysis' => 'Course Analysis Report'
                            ];
                            echo $report_titles[$report_type];
                            ?>
                        </h4>
                        <p class="card-category">
                            <?php echo date('F d, Y', strtotime($start_date)); ?> to <?php echo date('F d, Y', strtotime($end_date)); ?>
                            <?php echo $course_filter ? " - " . htmlspecialchars($course_filter) : ""; ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($report_data)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="reportTable">
                                    <thead class="text-primary">
                                        <tr>
                                            <?php
                                            // Dynamic table headers based on report type
                                            switch ($report_type) {
                                                case 'incident_summary':
                                                    echo '<th>Incident Type</th>
                                                          <th>Total Incidents</th>
                                                          <th>Pending</th>
                                                          <th>Under Review</th>
                                                          <th>Resolved</th>';
                                                    break;
                                                    
                                                case 'student_offenses':
                                                    echo '<th>Student Name</th>
                                                          <th>Course</th>
                                                          <th>Total Offenses</th>
                                                          <th>Offense Types</th>';
                                                    break;
                                                    
                                                case 'monthly_trends':
                                                    echo '<th>Month</th>
                                                          <th>Incident Type</th>
                                                          <th>Count</th>';
                                                    break;
                                                    
                                                case 'course_analysis':
                                                    echo '<th>Course</th>
                                                          <th>Total Incidents</th>
                                                          <th>Students Involved</th>
                                                          <th>Incidents per Student</th>
                                                          <th>Most Common Type</th>';
                                                    break;
                                            }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <?php
                                                switch ($report_type) {
                                                    case 'incident_summary':
                                                        echo "<td>" . htmlspecialchars($row['incident_type']) . "</td>
                                                              <td>" . $row['total_incidents'] . "</td>
                                                              <td><span class='badge badge-warning'>" . $row['pending'] . "</span></td>
                                                              <td><span class='badge badge-info'>" . $row['under_review'] . "</span></td>
                                                              <td><span class='badge badge-success'>" . $row['resolved'] . "</span></td>";
                                                        break;
                                                        
                                                    case 'student_offenses':
                                                        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>
                                                              <td>" . htmlspecialchars($row['course']) . "</td>
                                                              <td><span class='badge badge-danger'>" . $row['total_offenses'] . "</span></td>
                                                              <td>" . htmlspecialchars($row['offense_types']) . "</td>";
                                                        break;
                                                        
                                                    case 'monthly_trends':
                                                        echo "<td>" . $row['month'] . "</td>
                                                              <td>" . htmlspecialchars($row['type_name']) . "</td>
                                                              <td>" . $row['type_count'] . "</td>";
                                                        break;
                                                        
                                                    case 'course_analysis':
                                                        echo "<td>" . htmlspecialchars($row['course']) . "</td>
                                                              <td>" . $row['total_incidents'] . "</td>
                                                              <td>" . $row['students_involved'] . "</td>
                                                              <td>" . number_format($row['incidents_per_student'], 2) . "</td>
                                                              <td>" . htmlspecialchars($row['type_name']) . " (" . $row['type_count'] . ")</td>";
                                                        break;
                                                }
                                                ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Export Buttons
                            <div class="row mt-3">
                                <div class="col-md-12 text-right">
                                    <button onclick="exportToPDF()" class="btn btn-danger">
                                        <i class="material-icons">picture_as_pdf</i> Export PDF
                                    </button>
                                    <button onclick="exportToExcel()" class="btn btn-success">
                                        <i class="material-icons">table_chart</i> Export Excel
                                    </button>
                                    <button onclick="window.print()" class="btn btn-info">
                                        <i class="material-icons">print</i> Print Report
                                    </button>
                                </div>
                            </div>
                                            -->
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="material-icons">info</i>
                                No data found for the selected criteria.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <?php if ($report_type == 'monthly_trends' && !empty($chart_data)): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-info">
                        <h4 class="card-title">Monthly Incident Trends</h4>
                        <p class="card-category">Visual representation of incident types over time</p>
                    </div>
                    <div class="card-body">
                        <canvas id="trendsChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- JavaScript for Charts and Export -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($report_type == 'monthly_trends' && !empty($chart_data)): ?>
    // Monthly Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    const months = <?php echo json_encode(array_unique(array_column($report_data, 'month'))); ?>;
    
    const trendsChart = new Chart(trendsCtx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                <?php 
                $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
                $colorIndex = 0;
                foreach ($chart_data as $dataset): 
                ?>
                {
                    label: '<?php echo $dataset['label']; ?>',
                    data: <?php echo json_encode($dataset['data']); ?>,
                    backgroundColor: '<?php echo $colors[$colorIndex % count($colors)]; ?>',
                    borderColor: '<?php echo $colors[$colorIndex % count($colors)]; ?>',
                    borderWidth: 1
                },
                <?php 
                $colorIndex++;
                endforeach; 
                ?>
            ]
        },
        options: {
            responsive: true,
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
    <?php endif; ?>
});

// Export functions
function exportToPDF() {
    alert('PDF export functionality would be implemented here. This would require additional PDF generation libraries.');
    // In a real implementation, you would use jsPDF with html2canvas or similar
}

function exportToExcel() {
    try {
        const table = document.getElementById('reportTable');
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Report');
        XLSX.writeFile(wb, 'incident_report_<?php echo date('Y-m-d'); ?>.xlsx');
    } catch (error) {
        alert('Error exporting to Excel: ' + error.message);
    }
}

// Auto-refresh report when filters change
document.getElementById('report_type').addEventListener('change', function() {
    this.form.submit();
});
</script>

<?php
include('includes/footer.php');
?>