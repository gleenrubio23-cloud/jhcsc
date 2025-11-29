<?php
require '../includes/db_connect.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Fetch all users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

include('includes/header.php');
?>

<div class="content">

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="card-title">
                                    <i class="material-icons">people</i>
                                    Users Management
                                </h4>
                                <p class="card-category">Manage system users</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="functions/add_user.php" class="btn btn-secondary">
                                    <i class="material-icons">person_add</i> Add New User
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Display Messages -->
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <i class="material-icons">close</i>
                                </button>
                                <span><?php echo $_SESSION['error'];
                                unset($_SESSION['error']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <i class="material-icons">close</i>
                                </button>
                                <span><?php echo $_SESSION['success'];
                                unset($_SESSION['success']); ?></span>
                            </div>
                        <?php endif; ?>



                        <div class="table-responsive">
                            <table id="usersTable" class="table table-striped table-no-bordered table-hover"
                                cellspacing="0" width="100%">
                                <thead class="text-primary">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created At</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php
                                                switch ($user['role']) {
                                                    case 'admin':
                                                        echo 'danger';
                                                        break;
                                                    case 'teacher':
                                                        echo 'warning';
                                                        break;
                                                    case 'guidance':
                                                        echo 'info';
                                                        break;
                                                    default:
                                                        echo 'primary';
                                                }
                                                ?>">
                                                    <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                                            <td class="td-actions text-right">
                                                <a href="functions/edit_user.php?id=<?php echo $user['id']; ?>"
                                                    class="btn btn-success btn-link btn-sm" title="Edit User">
                                                    <i class="material-icons">edit</i>
                                                </a>
                                                <a href="functions/delete_user.php?id=<?php echo $user['id']; ?>"
                                                    class="btn btn-danger btn-link btn-sm" title="Delete User"
                                                    onclick="return confirm('Are you sure you want to delete this user?')">
                                                    <i class="material-icons">close</i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/core/jquery.min.js"></script>
<script src="../assets/js/plugins/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function () {
        $('#usersTable').DataTable({
            "pagingType": "full_numbers",
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search users...",
            }
        });
    });
</script>

<?php include('includes/footer.php'); ?>