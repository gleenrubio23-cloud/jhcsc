<?php
require '../../includes/db_connect.php';
session_start();

// Check if user is admin
/*if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied! Admin privileges required.";
    header("Location: ../dashboard.php");
    exit();
}*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];
        $role = $_POST['role'];

        // Check if username or email already exists
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->execute([$username, $email]);

        if ($check_stmt->fetch()) {
            $_SESSION['error'] = "Username or email already exists!";
        } else {
            // Insert new user
            $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $password, $email, $role]);

            $_SESSION['success'] = "User added successfully!";
            header("Location: ../users_list.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding user: " . $e->getMessage();
    }
}

include('../includes/header1.php');
?>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header card-header-success">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="card-title">
                                    <i class="material-icons">person_add</i>
                                    Add New User
                                </h4>
                                <p class="card-category">Create new system user</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="../users_list.php" class="btn btn-secondary">
                                    <i class="material-icons">arrow_back</i> Back to Users
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

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Username *</label>
                                        <input type="text" name="username" class="form-control" required
                                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Email *</label>
                                        <input type="email" name="email" class="form-control" required
                                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Password *</label>
                                        <input type="password" name="password" class="form-control" required
                                            placeholder="Enter password">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label></label>
                                        <select name="role" class="form-control" required>
                                            <option value="">Select Role</option>
                                            <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                            <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            <option value="guidance" <?php echo (isset($_POST['role']) && $_POST['role'] == 'guidance') ? 'selected' : ''; ?>>Guidance</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success pull-right">
                                        <i class="material-icons">person_add</i> Add User
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