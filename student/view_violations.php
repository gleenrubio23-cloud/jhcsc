<?php
session_start();

// Check if user is logged in and is a student BEFORE any output
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

include('includes/header.php');
include('../includes/db_connect.php');

// Get student ID from session (assuming student is logged in)
$student_id = $_SESSION['student_id']; // Replace with actual session variable

$filter_status = $_GET['status'] ?? 'all';
$filter_type = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Initialize variables with default values
$incidents = [];
$incident_types = [];
$status_stats = [];
$total_incidents = 0;
$error = '';

try {
    // Build the base query with correct column names
    $query = "SELECT i.*, 
                     it.type_name as incident_type,
                     s.status_name as status,
                     st.firstname, st.lastname, st.course, st.section
              FROM incidents i 
              LEFT JOIN incident_types it ON i.incident_type_id = it.id 
              LEFT JOIN incident_statuses s ON i.status_id = s.id 
              LEFT JOIN students st ON i.student_id = st.id 
              WHERE i.student_id = :student_id";

    $params = [':student_id' => $student_id];

    // Apply filters
    if ($filter_status !== 'all') {
        $query .= " AND s.status_name = :status";
        $params[':status'] = $filter_status;
    }

    if ($filter_type !== 'all') {
        $query .= " AND i.incident_type_id = :type";
        $params[':type'] = $filter_type;
    }

    if (!empty($search)) {
        $query .= " AND (i.description LIKE :search OR it.type_name LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $query .= " ORDER BY i.created_at DESC";

    // Prepare and execute the query
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get incident types for filter dropdown
    $types_query = "SELECT id, type_name as name FROM incident_types ORDER BY type_name";
    $types_stmt = $pdo->prepare($types_query);
    $types_stmt->execute();
    $incident_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get status counts for statistics
    $stats_query = "SELECT s.status_name, COUNT(*) as count 
                    FROM incidents i 
                    LEFT JOIN incident_statuses s ON i.status_id = s.id 
                    WHERE i.student_id = :student_id 
                    GROUP BY s.status_name";
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stats_stmt->execute();
    $status_stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total incidents
    $total_incidents = count($incidents);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!-- View Violations Content -->
<div class="content">
    <div class="container-fluid">
        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="card-title">My Violations & Incidents</h4>
                                <p class="card-category">View and track your reported incidents</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-info" data-toggle="modal"
                                    data-target="#filtersModal">
                                    <i class="material-icons">filter_alt</i> Filters
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">

                        <!-- Statistics Cards -->
                        <?php if (empty($error)): ?>
                            <!-- <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card card-stats">
                                        <div class="card-header card-header-warning card-header-icon">
                                            <div class="card-icon">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <p class="card-category">Total Incidents</p>
                                            <h3 class="card-title"><?php echo $total_incidents; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <?php foreach ($status_stats as $stat): ?>
                                    <div class="col-md-3">
                                        <div class="card card-stats">
                                            <div class="card-header card-header-<?php
                                            switch ($stat['status_name']) {
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
                                            ?> card-header-icon">
                                                <div class="card-icon">
                                                    <i class="fas fa-<?php
                                                    switch ($stat['status_name']) {
                                                        case 'Pending':
                                                            echo 'clock';
                                                            break;
                                                        case 'Under Review':
                                                            echo 'search';
                                                            break;
                                                        case 'Resolved':
                                                            echo 'check';
                                                            break;
                                                        default:
                                                            echo 'question';
                                                    }
                                                    ?>"></i>
                                                </div>
                                                <p class="card-category"><?php echo htmlspecialchars($stat['status_name']); ?>
                                                </p>
                                                <h3 class="card-title"><?php echo $stat['count']; ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>-->
                        <?php endif; ?>

                        <!-- Active Filters -->
                        <?php if (empty($error) && ($filter_status !== 'all' || $filter_type !== 'all' || !empty($search))): ?>
                            <div class="alert alert-info">
                                <strong>Active Filters:</strong>
                                <?php if ($filter_status !== 'all'): ?>
                                    <span class="badge badge-info">Status:
                                        <?php echo htmlspecialchars(ucfirst($filter_status)); ?></span>
                                <?php endif; ?>
                                <?php if ($filter_type !== 'all'): ?>
                                    <span class="badge badge-info">Type: <?php
                                    foreach ($incident_types as $type) {
                                        if ($type['id'] == $filter_type) {
                                            echo htmlspecialchars($type['name']);
                                            break;
                                        }
                                    }
                                    ?></span>
                                <?php endif; ?>
                                <?php if (!empty($search)): ?>
                                    <span class="badge badge-info">Search: "<?php echo htmlspecialchars($search); ?>"</span>
                                <?php endif; ?>
                                <a href="view_violations.php" class="btn btn-sm btn-outline-info ml-2">Clear All</a>
                            </div>
                        <?php endif; ?>

                        <!-- Incidents Table -->
                        <?php if (empty($error)): ?>
                            <?php if (count($incidents) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="text-primary">
                                            <tr>
                                                <th>#</th>
                                                <th>Incident Type</th>
                                                <th>Description</th>
                                                <th>Date Reported</th>
                                                <th>Status</th>
                                                <!--<th>Actions</th>-->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($incidents as $index => $incident): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($incident['incident_type'] ?? 'Not specified'); ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="incident-description">
                                                            <?php echo nl2br(htmlspecialchars($incident['description'] ?? 'No description')); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M j, Y', strtotime($incident['created_at'])); ?><br>
                                                        <small
                                                            class="text-muted"><?php echo date('g:i A', strtotime($incident['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php
                                                        switch ($incident['status']) {
                                                            case 'Resolved':
                                                                echo 'success';
                                                                break;
                                                            case 'Under Review':
                                                                echo 'info';
                                                                break;
                                                            case 'Pending':
                                                                echo 'warning';
                                                                break;
                                                            default:
                                                                echo 'secondary';
                                                        }
                                                        ?> badge-pill">
                                                            <?php echo htmlspecialchars($incident['status'] ?? 'Unknown'); ?>
                                                        </span>
                                                    </td>
                                                    <!--<td>
                                                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal"
                                                            data-target="#incidentModal"
                                                            data-incident='<?php echo htmlspecialchars(json_encode($incident)), ENT_QUOTES; ?>'>
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                    </td>-->
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h4>No incidents found</h4>
                                    <p class="text-muted">
                                        <?php if ($filter_status !== 'all' || $filter_type !== 'all' || !empty($search)): ?>
                                            No incidents match your current filters. <a href="view_violations.php">Clear filters</a>
                                            to see all incidents.
                                        <?php else: ?>
                                            You have no reported incidents. Keep up the good behavior!
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Modal -->
<div class="modal fade" id="filtersModal" tabindex="-1" role="dialog" aria-labelledby="filtersModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filtersModalLabel">Filter Incidents</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="GET" action="view_violations.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="all">All Statuses</option>
                            <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending
                            </option>
                            <option value="Under Review" <?php echo $filter_status === 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="Resolved" <?php echo $filter_status === 'Resolved' ? 'selected' : ''; ?>>
                                Resolved</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="type">Incident Type</label>
                        <select class="form-control" id="type" name="type">
                            <option value="all">All Types</option>
                            <?php foreach ($incident_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo $filter_type == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" class="form-control" id="search" name="search"
                            value="<?php echo htmlspecialchars($search); ?>" placeholder="Search in descriptions...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
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
                <!-- Details will be loaded here by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Incident Details Modal
        $('#incidentModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var incident = button.data('incident');
            var modal = $(this);

            var detailsHtml = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Incident Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Type:</strong></td>
                            <td>${incident.incident_type || 'Not specified'}</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <span class="badge badge-${getStatusBadgeClass(incident.status)}">
                                    ${incident.status || 'Unknown'}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Date Reported:</strong></td>
                            <td>${formatDate(incident.created_at)}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Student Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td>${incident.firstname} ${incident.lastname}</td>
                        </tr>
                        <tr>
                            <td><strong>Course:</strong></td>
                            <td>${incident.course || 'Not specified'}</td>
                        </tr>
                        <tr>
                            <td><strong>Section:</strong></td>
                            <td>${incident.section || 'Not specified'}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Description</h6>
                    <div class="card card-body bg-light">
                        ${incident.description ? incident.description.replace(/\n/g, '<br>') : 'No description provided.'}
                    </div>
                </div>
            </div>
        `;

            modal.find('#incidentDetails').html(detailsHtml);
        });

        function getStatusBadgeClass(status) {
            switch (status) {
                case 'Resolved': return 'success';
                case 'Under Review': return 'info';
                case 'Pending': return 'warning';
                default: return 'secondary';
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    });
</script>

<style>
    .card-stats {
        cursor: default;
    }

    .card-stats .card-header-icon {
        padding: 15px;
    }

    .incident-description {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .badge-pill {
        padding: 6px 12px;
        font-size: 12px;
    }

    .section-title {
        color: #495057;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 8px;
        margin-bottom: 15px;
    }
</style>

<?php
// Close connection
$pdo = null;
include('includes/footer.php');
?>