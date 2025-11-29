<?php
require '../../includes/db_connect.php';
session_start();

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
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check if username already exists
        $check_stmt = $pdo->prepare("SELECT id FROM students WHERE username = ?");
        $check_stmt->execute([$username]);

        if ($check_stmt->fetch()) {
            $_SESSION['error'] = "Username already exists! Please choose a different username.";
        } else {
            // Insert student
            $sql = "INSERT INTO students (firstname, middlename, lastname, birthdate, gender, nationality, contact, 
                    guardian_name, relationship, address, guardian_contact, course, section, year, username, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $firstname,
                $middlename,
                $lastname,
                $birthdate,
                $gender,
                $nationality,
                $contact,
                $guardian_name,
                $relationship,
                $address,
                $guardian_contact,
                $course,
                $section,
                $year,
                $username,
                $password
            ]);

            $_SESSION['success'] = "Student added successfully!";
            header("Location: ../studentlist.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding student: " . $e->getMessage();
    }
}

include('../includes/header1.php');
?>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header card-header-success">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="card-title">
                                    <i class="material-icons">person_add</i>
                                    Add New Student
                                </h4>
                                <p class="card-category">Create new student account</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="../studentlist.php" class="btn btn-secondary">
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
                                        <label class="font-weight-bold">First Name *</label>
                                        <input type="text" name="firstname" class="form-control" required
                                            value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Middle Name</label>
                                        <input type="text" name="middlename" class="form-control"
                                            value="<?php echo isset($_POST['middlename']) ? htmlspecialchars($_POST['middlename']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Last Name *</label>
                                        <input type="text" name="lastname" class="form-control" required
                                            value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Birthdate</label>
                                        <input type="date" name="birthdate" class="form-control"
                                            value="<?php echo isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Gender *</label>
                                        <div class="d-flex" style="margin-top: 10px;">
                                            <div class="form-check form-check-radio mr-3">
                                                <label class="form-check-label">
                                                    <input class="form-check-input" type="radio" name="gender"
                                                        value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'checked' : ''; ?> required>
                                                    Male
                                                    <span class="circle">
                                                        <span class="check"></span>
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="form-check form-check-radio">
                                                <label class="form-check-label">
                                                    <input class="form-check-input" type="radio" name="gender"
                                                        value="female" <?php echo (isset($_POST['gender']) && $_POST['year'] == 'female') ? 'checked' : ''; ?>>
                                                    Female
                                                    <span class="circle">
                                                        <span class="check"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Nationality</label>
                                        <input type="text" name="nationality" class="form-control"
                                            value="<?php echo isset($_POST['nationality']) ? htmlspecialchars($_POST['nationality']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Contact Number</label>
                                        <input type="text" name="contact" class="form-control"
                                            value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Address</label>
                                        <input type="text" name="address" class="form-control"
                                            value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Guardian Name</label>
                                        <input type="text" name="guardian_name" class="form-control"
                                            value="<?php echo isset($_POST['guardian_name']) ? htmlspecialchars($_POST['guardian_name']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Relationship</label>
                                        <input type="text" name="relationship" class="form-control"
                                            value="<?php echo isset($_POST['relationship']) ? htmlspecialchars($_POST['relationship']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Guardian Contact</label>
                                        <input type="text" name="guardian_contact" class="form-control"
                                            value="<?php echo isset($_POST['guardian_contact']) ? htmlspecialchars($_POST['guardian_contact']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Course</label>
                                        <input type="text" name="course" class="form-control"
                                            value="<?php echo isset($_POST['course']) ? htmlspecialchars($_POST['course']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Section</label>
                                        <input type="text" name="section" class="form-control"
                                            value="<?php echo isset($_POST['section']) ? htmlspecialchars($_POST['section']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Year Level *</label>
                                        <div class="row" style="margin-top: 10px;">
                                            <div class="col-12">
                                                <div class="form-check form-check-radio form-check-inline">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="radio" name="year"
                                                            value="1" <?php echo (isset($_POST['year']) && $_POST['year'] == '1') ? 'checked' : ''; ?> required>
                                                        First
                                                        <span class="circle">
                                                            <span class="check"></span>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="form-check form-check-radio form-check-inline">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="radio" name="year"
                                                            value="2" <?php echo (isset($_POST['year']) && $_POST['year'] == '2') ? 'checked' : ''; ?>>
                                                        Second
                                                        <span class="circle">
                                                            <span class="check"></span>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="form-check form-check-radio form-check-inline">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="radio" name="year"
                                                            value="3" <?php echo (isset($_POST['year']) && $_POST['year'] == '3') ? 'checked' : ''; ?>>
                                                        Third
                                                        <span class="circle">
                                                            <span class="check"></span>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="form-check form-check-radio form-check-inline">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="radio" name="year"
                                                            value="4" <?php echo (isset($_POST['year']) && $_POST['year'] == '4') ? 'checked' : ''; ?>>
                                                        Fourth
                                                        <span class="circle">
                                                            <span class="check"></span>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Username *</label>
                                        <input type="text" name="username" class="form-control" required
                                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Password *</label>
                                        <input type="password" name="password" class="form-control" required
                                            placeholder="Enter password">
                                        <small class="form-text text-muted">Password is required for new
                                            students</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success pull-right">
                                        <i class="material-icons">person_add</i> Add Student
                                    </button>
                                    <a href="../studentlist.php" class="btn btn-default pull-right mr-3">Cancel</a>
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