<?php
session_start();

// Check if user is logged in and is guidance
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guidance') {
    header('Location: ../index.php');
    exit();
}

include('../includes/db_connect.php');

// Add CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle ALL form submissions at the TOP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security token mismatch. Please try again.";
        header("Location: calendar.php");
        exit();
    }

    if (isset($_POST['delete_event'])) {
        $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

        if ($event_id) {
            try {
                // Get event title for confirmation message
                $stmt = $pdo->prepare("SELECT title FROM guidance_calendar WHERE id = ? AND created_by = ?");
                $stmt->execute([$event_id, $_SESSION['user_id']]);
                $event = $stmt->fetch();

                if ($event) {
                    $delete_stmt = $pdo->prepare("DELETE FROM guidance_calendar WHERE id = ? AND created_by = ?");
                    $delete_stmt->execute([$event_id, $_SESSION['user_id']]);

                    if ($delete_stmt->rowCount() > 0) {
                        $_SESSION['success'] = "Event '{$event['title']}' deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Event not found or you don't have permission to delete it.";
                    }
                } else {
                    $_SESSION['error'] = "Event not found.";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Failed to delete event: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Invalid event ID.";
        }

        header("Location: calendar.php");
        exit();
    }

    if (isset($_POST['add_event'])) {
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
        $start_datetime = filter_input(INPUT_POST, 'start_datetime', FILTER_SANITIZE_STRING);
        $end_datetime = !empty($_POST['end_datetime']) ? filter_input(INPUT_POST, 'end_datetime', FILTER_SANITIZE_STRING) : NULL;
        $color = filter_input(INPUT_POST, 'color', FILTER_SANITIZE_STRING) ?: '#2196f3';
        $created_by = $_SESSION['user_id'];

        // Validation
        $errors = [];

        if (empty($title) || strlen($title) > 255) {
            $errors[] = "Title is required and must be less than 255 characters.";
        }

        if (empty($start_datetime) || !strtotime($start_datetime)) {
            $errors[] = "Valid start date and time are required.";
        }

        if ($end_datetime && !strtotime($end_datetime)) {
            $errors[] = "Invalid end date and time format.";
        }

        if ($end_datetime && strtotime($end_datetime) < strtotime($start_datetime)) {
            $errors[] = "End date/time cannot be before start date/time.";
        }

        if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            $errors[] = "Invalid color format.";
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO guidance_calendar (title, description, start_datetime, end_datetime, color, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $start_datetime, $end_datetime, $color, $created_by]);

                $_SESSION['success'] = "Event added successfully!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Failed to add event: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = implode(" ", $errors);
        }

        header("Location: calendar.php");
        exit();
    }

    if (isset($_POST['update_event'])) {
        $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
        $start_datetime = filter_input(INPUT_POST, 'start_datetime', FILTER_SANITIZE_STRING);
        $end_datetime = !empty($_POST['end_datetime']) ? filter_input(INPUT_POST, 'end_datetime', FILTER_SANITIZE_STRING) : NULL;
        $color = filter_input(INPUT_POST, 'color', FILTER_SANITIZE_STRING) ?: '#2196f3';

        if ($event_id) {
            // Validation (same as add event)
            $errors = [];

            if (empty($title) || strlen($title) > 255) {
                $errors[] = "Title is required and must be less than 255 characters.";
            }

            if (empty($start_datetime) || !strtotime($start_datetime)) {
                $errors[] = "Valid start date and time are required.";
            }

            if ($end_datetime && !strtotime($end_datetime)) {
                $errors[] = "Invalid end date and time format.";
            }

            if ($end_datetime && strtotime($end_datetime) < strtotime($start_datetime)) {
                $errors[] = "End date/time cannot be before start date/time.";
            }

            if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
                $errors[] = "Invalid color format.";
            }

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE guidance_calendar 
                        SET title = ?, description = ?, start_datetime = ?, end_datetime = ?, color = ?, updated_at = NOW()
                        WHERE id = ? AND created_by = ?
                    ");
                    $stmt->execute([$title, $description, $start_datetime, $end_datetime, $color, $event_id, $_SESSION['user_id']]);

                    if ($stmt->rowCount() > 0) {
                        $_SESSION['success'] = "Event updated successfully!";
                    } else {
                        $_SESSION['error'] = "Event not found or you don't have permission to update it.";
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Failed to update event: " . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = implode(" ", $errors);
            }
        } else {
            $_SESSION['error'] = "Invalid event ID.";
        }

        header("Location: calendar.php");
        exit();
    }
}

// Now include header and display page
include('includes/header.php');

// Check for session messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Get all events for the calendar
try {
    $stmt = $pdo->prepare("
        SELECT gc.*, u.username as created_by_name 
        FROM guidance_calendar gc 
        JOIN users u ON gc.created_by = u.id 
        ORDER BY gc.start_datetime ASC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to load events: " . $e->getMessage();
    $events = [];
}

// Prepare events for JavaScript - FIXED FORMAT
$calendar_events = [];
foreach ($events as $event) {
    $calendar_event = [
        'id' => $event['id'],
        'title' => htmlspecialchars($event['title'], ENT_QUOTES),
        'description' => htmlspecialchars($event['description'] ?? '', ENT_QUOTES),
        'start' => $event['start_datetime'],
        'color' => $event['color'],
        'created_by' => htmlspecialchars($event['created_by_name'], ENT_QUOTES)
    ];

    if (!empty($event['end_datetime'])) {
        $calendar_event['end'] = $event['end_datetime'];
    }

    $calendar_events[] = $calendar_event;
}

// Debug: Check if events are being loaded
error_log("Calendar Events Count: " . count($calendar_events));
foreach ($calendar_events as $event) {
    error_log("Event: " . $event['title'] . " - " . $event['start']);
}
?>

<div class="content">
    <div class="container-fluid">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card card-calendar">
                    <div class="card-header card-header-primary">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="card-title">Guidance Office Calendar</h4>
                                <p class="card-category">Manage activities and appointments</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-white btn-sm" data-toggle="modal"
                                    data-target="#addEventModal">
                                    <i class="material-icons">add</i> Add New Event
                                </button>
                                <button type="button" class="btn btn-white btn-sm" id="refreshCalendar">
                                    <i class="material-icons">refresh</i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Debug info (remove in production) -->
                        <div class="alert alert-info d-none" id="debugInfo">
                            <strong>Debug Info:</strong>
                            <div id="eventCount">Events loaded: <?php echo count($calendar_events); ?></div>
                            <div id="calendarStatus">Calendar status: Initializing...</div>
                        </div>

                        <div id="fullCalendar"></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header card-header-info">
                        <h4 class="card-title">Upcoming Events</h4>
                        <p class="card-category">Next 7 days</p>
                    </div>
                    <div class="card-body">
                        <?php
                        $upcoming_events = array_filter($events, function ($event) {
                            $event_date = strtotime($event['start_datetime']);
                            $next_week = strtotime('+7 days');
                            $today = strtotime('today');
                            return $event_date >= $today && $event_date <= $next_week;
                        });

                        // Sort upcoming events by date
                        usort($upcoming_events, function ($a, $b) {
                            return strtotime($a['start_datetime']) - strtotime($b['start_datetime']);
                        });
                        ?>

                        <?php if (empty($upcoming_events)): ?>
                            <div class="text-center py-3">
                                <i class="material-icons text-muted" style="font-size: 48px;">event_busy</i>
                                <p class="text-muted">No upcoming events</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcoming_events as $event): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('M j', strtotime($event['start_datetime'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1 small text-muted">
                                            <?php
                                            if (!empty($event['end_datetime'])) {
                                                echo date('g:i A', strtotime($event['start_datetime'])) . ' - ' .
                                                    date('g:i A', strtotime($event['end_datetime']));
                                            } else {
                                                echo date('g:i A', strtotime($event['start_datetime']));
                                            }
                                            ?>
                                        </p>
                                        <?php if ($event['description']): ?>
                                            <p class="mb-1 small">
                                                <?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>
                                                <?php echo strlen($event['description']) > 100 ? '...' : ''; ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span class="badge badge-pill"
                                                style="background-color: <?php echo $event['color']; ?>; color: white;">
                                                <?php echo htmlspecialchars($event['created_by_name']); ?>
                                            </span>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-info btn-sm edit-event"
                                                    data-event-id="<?php echo $event['id']; ?>"
                                                    data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                                    <i class="material-icons" style="font-size: 16px;">edit</i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm delete-event-simple"
                                                    data-event-id="<?php echo $event['id']; ?>"
                                                    data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                                    <i class="material-icons" style="font-size: 16px;">delete</i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats Card -->
                <div class="card mt-4">
                    <div class="card-header card-header-success">
                        <h4 class="card-title">Calendar Stats</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h3><?php echo count($events); ?></h3>
                                <p class="text-muted">Total Events</p>
                            </div>
                            <div class="col-6">
                                <h3><?php echo count($upcoming_events); ?></h3>
                                <p class="text-muted">Upcoming Events</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1" role="dialog" aria-labelledby="addEventModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEventModalLabel">Add New Event</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" id="addEventForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="title">Event Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required maxlength="255">
                        <small class="form-text text-muted">Maximum 255 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                            maxlength="500"></textarea>
                        <small class="form-text text-muted">Maximum 500 characters</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_datetime">Start Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="start_datetime"
                                    name="start_datetime" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_datetime">End Date & Time</label>
                                <input type="datetime-local" class="form-control" id="end_datetime" name="end_datetime">
                                <small class="form-text text-muted">Optional</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="color">Event Color</label>
                        <div class="d-flex align-items-center">
                            <select class="form-control mr-2" id="color" name="color">
                                <option value="#2196f3">Blue</option>
                                <option value="#4caf50">Green</option>
                                <option value="#ff9800">Orange</option>
                                <option value="#f44336">Red</option>
                                <option value="#9c27b0">Purple</option>
                                <option value="#607d8b">Gray</option>
                            </select>
                            <span id="colorPreview" class="badge badge-pill"
                                style="width: 30px; height: 30px; background-color: #2196f3;"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="add_event">Add Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1" role="dialog" aria-labelledby="editEventModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" id="editEventForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" id="edit_event_id" name="event_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_title">Event Title *</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required maxlength="255">
                        <small class="form-text text-muted">Maximum 255 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"
                            maxlength="500"></textarea>
                        <small class="form-text text-muted">Maximum 500 characters</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_start_datetime">Start Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="edit_start_datetime"
                                    name="start_datetime" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_end_datetime">End Date & Time</label>
                                <input type="datetime-local" class="form-control" id="edit_end_datetime"
                                    name="end_datetime">
                                <small class="form-text text-muted">Optional</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_color">Event Color</label>
                        <div class="d-flex align-items-center">
                            <select class="form-control mr-2" id="edit_color" name="color">
                                <option value="#2196f3">Blue</option>
                                <option value="#4caf50">Green</option>
                                <option value="#ff9800">Orange</option>
                                <option value="#f44336">Red</option>
                                <option value="#9c27b0">Purple</option>
                                <option value="#607d8b">Gray</option>
                            </select>
                            <span id="editColorPreview" class="badge badge-pill"
                                style="width: 30px; height: 30px; background-color: #2196f3;"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="update_event">Update Event</button>
                    <button type="button" class="btn btn-danger" id="deleteEventBtn">Delete Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/core/jquery.min.js"></script>
<script src="../assets/js/plugins/moment.min.js"></script>
<script src="../assets/js/plugins/sweetalert2.js"></script>
<script src="../assets/js/plugins/fullcalendar.min.js"></script>

<script>
    $(document).ready(function () {
        console.log('Document ready - initializing calendar...');

        let calendar = null;

        // Initialize FullCalendar - SIMPLIFIED VERSION
        function initializeCalendar() {
            console.log('Initializing calendar...');

            try {
                // Destroy existing calendar if it exists
                if (calendar) {
                    $('#fullCalendar').fullCalendar('destroy');
                    console.log('Destroyed existing calendar');
                }

                // Initialize new calendar
                calendar = $('#fullCalendar').fullCalendar({
                    header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'month,agendaWeek,agendaDay,listWeek'
                    },
                    defaultDate: new Date(),
                    navLinks: true,
                    editable: false,
                    eventLimit: true,
                    events: <?php echo json_encode($calendar_events); ?>,
                    eventClick: function (calEvent, jsEvent, view) {
                        console.log('Event clicked:', calEvent);

                        Swal.fire({
                            title: calEvent.title,
                            html: `
                            <div class="text-left">
                                ${calEvent.description ? `<p><strong>Description:</strong> ${calEvent.description}</p>` : ''}
                                <p><strong>Start:</strong> ${moment(calEvent.start).format('MMMM Do YYYY, h:mm A')}</p>
                                ${calEvent.end ? `<p><strong>End:</strong> ${moment(calEvent.end).format('MMMM Do YYYY, h:mm A')}</p>` : ''}
                                <p><strong>Created by:</strong> ${calEvent.created_by}</p>
                            </div>
                        `,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'Edit',
                            cancelButtonText: 'Close',
                            showDenyButton: true,
                            denyButtonText: 'Delete'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                loadEventData(calEvent.id);
                            } else if (result.isDenied) {
                                deleteEvent(calEvent.id, calEvent.title);
                            }
                        });
                    },
                    eventRender: function (event, element) {
                        console.log('Rendering event:', event.title);
                        element.attr('title', event.title);
                    },
                    loading: function (isLoading, view) {
                        console.log('Calendar loading:', isLoading);
                        $('#calendarStatus').text('Calendar status: ' + (isLoading ? 'Loading...' : 'Ready'));
                    },
                    viewRender: function (view, element) {
                        console.log('View rendered:', view.name);
                    }
                });

                console.log('Calendar initialized successfully');
                $('#calendarStatus').text('Calendar status: Initialized successfully');

            } catch (error) {
                console.error('Error initializing calendar:', error);
                $('#calendarStatus').text('Calendar status: Error - ' + error.message);

                // Show error to user
                Swal.fire({
                    icon: 'error',
                    title: 'Calendar Error',
                    text: 'Failed to initialize calendar. Please check console for details.'
                });
            }
        }

        // Test events data
        console.log('Events data:', <?php echo json_encode($calendar_events); ?>);

        // Show debug info in development
        $('#debugInfo').removeClass('d-none');

        // Initialize calendar on page load
        initializeCalendar();

        // Set default datetime for add form
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        $('#start_datetime').val(now.toISOString().slice(0, 16));

        // Color preview
        $('#color, #edit_color').change(function () {
            const color = $(this).val();
            const previewId = $(this).attr('id') === 'color' ? '#colorPreview' : '#editColorPreview';
            $(previewId).css('background-color', color);
        });

        // Initialize color previews
        $('#colorPreview').css('background-color', $('#color').val());
        $('#editColorPreview').css('background-color', $('#edit_color').val());

        // Refresh calendar
        $('#refreshCalendar').click(function () {
            console.log('Refreshing calendar...');
            initializeCalendar();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Calendar refreshed',
                showConfirmButton: false,
                timer: 1500
            });
        });

        // SIMPLE DELETE - Redirect method
        $(document).on('click', '.delete-event-simple', function () {
            const eventId = $(this).data('event-id');
            const eventTitle = $(this).data('event-title');
            deleteEvent(eventId, eventTitle);
        });

        // Delete from edit modal
        $('#deleteEventBtn').click(function () {
            const eventId = $('#edit_event_id').val();
            const eventTitle = $('#edit_title').val();
            deleteEvent(eventId, eventTitle);
        });

        // Edit event
        $(document).on('click', '.edit-event', function () {
            const eventId = $(this).data('event-id');
            loadEventData(eventId);
        });

        // Form validation
        $('#addEventForm, #editEventForm').submit(function (e) {
            const startDateTime = $(this).find('input[name="start_datetime"]').val();
            const endDateTime = $(this).find('input[name="end_datetime"]').val();

            if (endDateTime && new Date(endDateTime) <= new Date(startDateTime)) {
                e.preventDefault();
                Swal.fire('Error', 'End date/time must be after start date/time', 'error');
                return false;
            }

            return true;
        });

        function deleteEvent(eventId, eventTitle) {
            Swal.fire({
                title: 'Are you sure?',
                html: `You are about to delete: <strong>"${eventTitle}"</strong><br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create a simple form and submit it
                    const form = $('<form>').attr({
                        method: 'POST',
                        action: 'calendar.php'
                    }).append(
                        $('<input>').attr({ type: 'hidden', name: 'event_id', value: eventId }),
                        $('<input>').attr({ type: 'hidden', name: 'delete_event', value: '1' }),
                        $('<input>').attr({ type: 'hidden', name: 'csrf_token', value: '<?php echo $_SESSION['csrf_token']; ?>' })
                    );

                    $('body').append(form);
                    form.submit();
                }
            });
        }

        function loadEventData(eventId) {
            console.log('Loading event data for ID:', eventId);

            // Show loading state immediately
            $('#editEventModal').modal('show');
            $('#editEventModal .modal-body').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading event data...</p>
                </div>
            `);

            $.ajax({
                url: 'get_event.php',
                type: 'POST',
                data: {
                    id: eventId,
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                },
                dataType: 'json',
                success: function (response) {
                    console.log('Event data loaded:', response);

                    if (response.success) {
                        const event = response.event;

                        // Populate edit form
                        $('#editEventModal .modal-body').html(`
                            <div class="form-group">
                                <label for="edit_title">Event Title *</label>
                                <input type="text" class="form-control" id="edit_title" name="title" value="${event.title}" required maxlength="255">
                                <small class="form-text text-muted">Maximum 255 characters</small>
                            </div>
                            <div class="form-group">
                                <label for="edit_description">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3" maxlength="500">${event.description || ''}</textarea>
                                <small class="form-text text-muted">Maximum 500 characters</small>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="edit_start_datetime">Start Date & Time *</label>
                                        <input type="datetime-local" class="form-control" id="edit_start_datetime" name="start_datetime" value="${event.start_datetime.slice(0, 16)}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="edit_end_datetime">End Date & Time</label>
                                        <input type="datetime-local" class="form-control" id="edit_end_datetime" name="end_datetime" value="${event.end_datetime ? event.end_datetime.slice(0, 16) : ''}">
                                        <small class="form-text text-muted">Optional</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="edit_color">Event Color</label>
                                <div class="d-flex align-items-center">
                                    <select class="form-control mr-2" id="edit_color" name="color">
                                        <option value="#2196f3" ${event.color === '#2196f3' ? 'selected' : ''}>Blue</option>
                                        <option value="#4caf50" ${event.color === '#4caf50' ? 'selected' : ''}>Green</option>
                                        <option value="#ff9800" ${event.color === '#ff9800' ? 'selected' : ''}>Orange</option>
                                        <option value="#f44336" ${event.color === '#f44336' ? 'selected' : ''}>Red</option>
                                        <option value="#9c27b0" ${event.color === '#9c27b0' ? 'selected' : ''}>Purple</option>
                                        <option value="#607d8b" ${event.color === '#607d8b' ? 'selected' : ''}>Gray</option>
                                    </select>
                                    <span id="editColorPreview" class="badge badge-pill" style="width: 30px; height: 30px; background-color: ${event.color};"></span>
                                </div>
                            </div>
                        `);

                        $('#edit_event_id').val(event.id);

                        // Update color preview
                        $('#edit_color').change(function () {
                            $('#editColorPreview').css('background-color', $(this).val());
                        });

                    } else {
                        $('#editEventModal').modal('hide');
                        Swal.fire('Error', response.error || 'Failed to load event data', 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading event data:', error);
                    $('#editEventModal').modal('hide');
                    Swal.fire('Error', 'Failed to load event data. Please try again.', 'error');
                }
            });
        }

        // Test if FullCalendar is loaded
        if (typeof $.fullCalendar === 'undefined') {
            console.error('FullCalendar not loaded! Check if the script is included correctly.');
            $('#calendarStatus').text('Calendar status: FullCalendar library not loaded');
        } else {
            console.log('FullCalendar library loaded successfully');
        }
    });
</script>

<style>
    /* Ensure calendar has proper height */
    #fullCalendar {
        min-height: 600px;
        background: white;
    }

    .fc-header-toolbar {
        padding: 10px;
        margin-bottom: 0 !important;
    }

    .fc-view {
        border: 1px solid #ddd;
    }

    /* Debug styles */
    #debugInfo {
        font-size: 12px;
        margin-bottom: 10px;
    }
</style>

<?php
$pdo = null;
include('includes/footer.php');
?>