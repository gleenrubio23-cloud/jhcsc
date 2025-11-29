<?php
require '../../includes/db_connect.php';
session_start();

// Check if user is admin
/*if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied! Admin privileges required.";
    header("Location: ../dashboard.php");
    exit();
}*/

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No user ID provided!";
    header("Location: users_list.php");
    exit();
}

$user_id = $_GET['id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check if user exists
if (!$user) {
    $_SESSION['error'] = "User not found!";
    header("Location: users_list.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];

        // Check if username or email already exists (excluding current user)
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check_stmt->execute([$username, $email, $user_id]);

        if ($check_stmt->fetch()) {
            $_SESSION['error'] = "Username or email already exists!";
        } else {
            // Handle password update
            $password_update = "";
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $password_update = ", password = :password";
            }

            // Update user
            $sql = "UPDATE users SET username = :username, email = :email, role = :role $password_update WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            $params = [
                ':username' => $username,
                ':email' => $email,
                ':role' => $role,
                ':id' => $user_id
            ];

            if (!empty($_POST['password'])) {
                $params[':password'] = $password;
            }

            if ($stmt->execute($params)) {
                $_SESSION['success'] = "User updated successfully!";
                header("Location: users_list.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to update user!";
            }
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating user: " . $e->getMessage();
    }
}

include('../includes/header1.php');
?>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="card-title">
                                    <i class="material-icons">edit</i>
                                    Edit User
                                </h4>
                                <p class="card-category">Update user information</p>
                            </div>
                            <!--<div class="col-md-6 text-right">
                                <a href="../users_list.php" class="btn btn-secondary">
                                    <i class="material-icons">arrow_back</i> Back to Users
                                </a>
                            </div>-->
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

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Username *</label>
                                        <input type="text" name="username" class="form-control" required
                                            value="<?php echo htmlspecialchars($user['username']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Email *</label>
                                        <input type="email" name="email" class="form-control" required
                                            value="<?php echo htmlspecialchars($user['email']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Password (Leave blank to keep current)</label>
                                        <input type="password" name="password" class="form-control"
                                            placeholder="Enter new password">
                                    </div>
                                </div>
                                <!--<div class="col-md-6">
                                    <div class="form-group">
                                        <label>Role *</label>
                                        <select name="role" class="form-control" required>
                                            <option value="student" <?php echo ($user['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                            <option value="teacher" <?php echo ($user['role'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                            <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            <option value="guidance" <?php echo ($user['role'] == 'guidance') ? 'selected' : ''; ?>>Guidance</option>
                                        </select>
                                    </div>
                                </div>-->
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Account Created:</label>
                                        <p class="form-control-static">
                                            <?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-warning pull-right">
                                        <i class="material-icons">update</i> Update User
                                    </button>
                                    <a href="../users_list.php" class="btn btn-default pull-right mr-3">Cancel</a>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>