<?php
require '../includes/db_connect.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}


// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No student ID provided!";
    header("Location: students.php");
    exit();
}

$student_id = $_GET['id'];

// Fetch student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Check if student exists
if (!$student) {
    $_SESSION['error'] = "Student not found!";
    header("Location: students.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $firstname = $_POST['firstname'];
        $middlename = $_POST['middlename'];
        $lastname = $_POST['lastname'];
        $birthdate = $_POST['birthdate'];
        $gender = $_POST['gender'];
        $nationality = $_POST['nationality'];
        $contact = $_POST['contact'];
        $guardian_name = $_POST['guardian_name'];
        $relationship = $_POST['relationship'];
        $address = $_POST['address'];
        $guardian_contact = $_POST['guardian_contact'];
        $course = $_POST['course'];
        $section = $_POST['section'];
        $year = $_POST['year'];
        $username = $_POST['username'];

        // Handle password update (only if provided)
        $password_update = "";
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_update = ", password = :password";
        }

        // Update query
        $sql = "UPDATE students SET 
                firstname = :firstname,
                middlename = :middlename,
                lastname = :lastname,
                birthdate = :birthdate,
                gender = :gender,
                nationality = :nationality,
                contact = :contact,
                guardian_name = :guardian_name,
                relationship = :relationship,
                address = :address,
                guardian_contact = :guardian_contact,
                course = :course,
                section = :section,
                year = :year,
                username = :username
                $password_update
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $params = [
            ':firstname' => $firstname,
            ':middlename' => $middlename,
            ':lastname' => $lastname,
            ':birthdate' => $birthdate,
            ':gender' => $gender,
            ':nationality' => $nationality,
            ':contact' => $contact,
            ':guardian_name' => $guardian_name,
            ':relationship' => $relationship,
            ':address' => $address,
            ':guardian_contact' => $guardian_contact,
            ':course' => $course,
            ':section' => $section,
            ':year' => $year,
            ':username' => $username,
            ':id' => $student_id
        ];

        if (!empty($_POST['password'])) {
            $params[':password'] = $password;
        }

        if ($stmt->execute($params)) {
            $_SESSION['success'] = "Student updated successfully!";
            header("Location: studentlist.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update student!";
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Include header
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
                                    <i class="material-icons">edit</i>
                                    Edit Student
                                </h4>
                                <p class="card-category">Update student information</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="students.php" class="btn btn-secondary">
                                    <i class="material-icons">arrow_back</i> Back to Students
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

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">First Name *</label>
                                        <input type="text" name="firstname" class="form-control"
                                            value="<?php echo htmlspecialchars($student['firstname']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Middle Name</label>
                                        <input type="text" name="middlename" class="form-control"
                                            value="<?php echo htmlspecialchars($student['middlename'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Last Name *</label>
                                        <input type="text" name="lastname" class="form-control"
                                            value="<?php echo htmlspecialchars($student['lastname']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Birthdate</label>
                                        <input type="date" name="birthdate" class="form-control"
                                            value="<?php echo htmlspecialchars($student['birthdate'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Gender *</label>
                                        <select name="gender" class="form-control" required>
                                            <option value="male" <?php echo ($student['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo ($student['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Nationality</label>
                                        <input type="text" name="nationality" class="form-control"
                                            value="<?php echo htmlspecialchars($student['nationality'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Contact Number</label>
                                        <input type="text" name="contact" class="form-control"
                                            value="<?php echo htmlspecialchars($student['contact'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Address</label>
                                        <input type="text" name="address" class="form-control"
                                            value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Guardian Name</label>
                                        <input type="text" name="guardian_name" class="form-control"
                                            value="<?php echo htmlspecialchars($student['guardian_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Relationship</label>
                                        <input type="text" name="relationship" class="form-control"
                                            value="<?php echo htmlspecialchars($student['relationship'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Guardian Contact</label>
                                        <input type="text" name="guardian_contact" class="form-control"
                                            value="<?php echo htmlspecialchars($student['guardian_contact'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Course</label>
                                        <input type="text" name="course" class="form-control"
                                            value="<?php echo htmlspecialchars($student['course'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Section</label>
                                        <input type="text" name="section" class="form-control"
                                            value="<?php echo htmlspecialchars($student['section'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Year Level</label>
                                        <select name="year" class="form-control">
                                            <option value="">Select Year</option>
                                            <option value="1" <?php echo ($student['year'] == 1) ? 'selected' : ''; ?>>
                                                First Year</option>
                                            <option value="2" <?php echo ($student['year'] == 2) ? 'selected' : ''; ?>>
                                                Second Year</option>
                                            <option value="3" <?php echo ($student['year'] == 3) ? 'selected' : ''; ?>>
                                                Third Year</option>
                                            <option value="4" <?php echo ($student['year'] == 4) ? 'selected' : ''; ?>>
                                                Fourth Year</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Username *</label>
                                        <input type="text" name="username" class="form-control"
                                            value="<?php echo htmlspecialchars($student['username']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="bmd-label-floating">Password (Leave blank to keep current)</label>
                                        <input type="password" name="password" class="form-control"
                                            placeholder="Enter new password">
                                        <small class="form-text text-muted">Only enter if you want to change the
                                            password</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary pull-right">
                                        <i class="material-icons">update</i> Update Student
                                    </button>
                                    <a href="students.php" class="btn btn-default pull-right mr-3">Cancel</a>
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

<?php include('includes/footer.php'); ?>