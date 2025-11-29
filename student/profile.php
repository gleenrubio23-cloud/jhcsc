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

$error = '';
$success = '';

try {
    // Fetch student information
    $student_query = "SELECT * FROM students WHERE id = :student_id";
    $student_stmt = $pdo->prepare($student_query);
    $student_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $student_stmt->execute();
    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $error = "Student not found!";
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        // Personal Information
        $firstname = trim($_POST['firstname'] ?? '');
        $middlename = trim($_POST['middlename'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $birthdate = $_POST['birthdate'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $nationality = trim($_POST['nationality'] ?? '');
        $contact = trim($_POST['contact'] ?? '');

        // Academic Information
        $course = trim($_POST['course'] ?? '');
        $section = trim($_POST['section'] ?? '');
        $year = $_POST['year'] ?? '';
        $username = trim($_POST['username'] ?? '');

        // Guardian Information
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $relationship = trim($_POST['relationship'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $guardian_contact = trim($_POST['guardian_contact'] ?? '');

        // Password change (optional)
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate required fields
        if (empty($firstname) || empty($lastname) || empty($gender) || empty($username)) {
            $error = "Please fill in all required fields (First Name, Last Name, Gender, Username).";
        } else {
            // Check if username already exists (excluding current student)
            $username_check_query = "SELECT id FROM students WHERE username = :username AND id != :student_id";
            $username_check_stmt = $pdo->prepare($username_check_query);
            $username_check_stmt->bindParam(':username', $username);
            $username_check_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $username_check_stmt->execute();

            if ($username_check_stmt->rowCount() > 0) {
                $error = "Username already exists. Please choose a different username.";
            } else {
                // Handle password change if provided
                $password_update = '';
                if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
                    if (!password_verify($current_password, $student['password'])) {
                        $error = "Current password is incorrect!";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "New passwords do not match!";
                    } elseif (strlen($new_password) < 6) {
                        $error = "New password must be at least 6 characters long!";
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $password_update = ", password = :password";
                    }
                }

                if (empty($error)) {
                    // Update student profile
                    $update_query = "UPDATE students SET 
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
                                    WHERE id = :student_id";

                    $update_stmt = $pdo->prepare($update_query);
                    $update_stmt->bindParam(':firstname', $firstname);
                    $update_stmt->bindParam(':middlename', $middlename);
                    $update_stmt->bindParam(':lastname', $lastname);
                    $update_stmt->bindParam(':birthdate', $birthdate);
                    $update_stmt->bindParam(':gender', $gender);
                    $update_stmt->bindParam(':nationality', $nationality);
                    $update_stmt->bindParam(':contact', $contact);
                    $update_stmt->bindParam(':guardian_name', $guardian_name);
                    $update_stmt->bindParam(':relationship', $relationship);
                    $update_stmt->bindParam(':address', $address);
                    $update_stmt->bindParam(':guardian_contact', $guardian_contact);
                    $update_stmt->bindParam(':course', $course);
                    $update_stmt->bindParam(':section', $section);
                    $update_stmt->bindParam(':year', $year, PDO::PARAM_INT);
                    $update_stmt->bindParam(':username', $username);
                    $update_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);

                    // Bind password parameter if changing password
                    if (!empty($password_update)) {
                        $update_stmt->bindParam(':password', $hashed_password);
                    }

                    if ($update_stmt->execute()) {
                        $success = "Profile updated successfully!" . (!empty($password_update) ? " Password has been changed." : "");
                        // Refresh student data
                        $student_stmt->execute();
                        $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $error = "Failed to update profile. Please try again.";
                    }
                }
            }
        }
    }

    // Handle photo upload separately
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_photo']) && isset($_FILES['photo'])) {
        $photo = $_FILES['photo'];

        // Check if file was uploaded without errors
        if ($photo['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($photo['type'], $allowed_types)) {
                $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
            } elseif ($photo['size'] > $max_size) {
                $error = "File size must be less than 2MB.";
            } else {
                // Generate unique filename
                $file_extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
                $filename = 'student_' . $student_id . '_' . time() . '.' . $file_extension;
                $upload_path = 'uploads/students/' . $filename;

                // Create uploads directory if it doesn't exist
                if (!is_dir('uploads/students')) {
                    mkdir('uploads/students', 0755, true);
                }

                if (move_uploaded_file($photo['tmp_name'], $upload_path)) {
                    // Update photo path in database
                    $photo_query = "UPDATE students SET photo = :photo WHERE id = :student_id";
                    $photo_stmt = $pdo->prepare($photo_query);
                    $photo_stmt->bindParam(':photo', $upload_path);
                    $photo_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);

                    if ($photo_stmt->execute()) {
                        $success = "Profile photo updated successfully!";
                        // Refresh student data
                        $student_stmt->execute();
                        $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $error = "Failed to update photo in database.";
                    }
                } else {
                    $error = "Failed to upload photo. Please try again.";
                }
            }
        } else {
            $error = "Error uploading file. Please try again.";
        }
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Safe data retrieval function
function getStudentData($data, $key, $default = '')
{
    return isset($data[$key]) && !empty($data[$key]) ? $data[$key] : $default;
}
?>

<!-- Profile Content -->
<div class="content">
    <div class="container-fluid">
        <!-- Alert Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information Column -->
            <div class="col-md-4">
                <!-- Profile Card -->
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title">Profile Information</h4>
                    </div>
                    <div class="card-body text-center">
                        <!-- Profile Photo -->
                        <div class="mb-3">
                            <?php if (!empty($student['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($student['photo']); ?>" alt="Profile Photo"
                                    class="img-fluid rounded-circle"
                                    style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                    style="width: 150px; height: 150px;">
                                    <i class="fas fa-user fa-3x"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h4>
                            <?php echo htmlspecialchars(getStudentData($student, 'firstname') . ' ' . getStudentData($student, 'middlename') . ' ' . getStudentData($student, 'lastname')); ?>
                        </h4>
                        <p class="text-muted">
                            <?php echo htmlspecialchars(getStudentData($student, 'course', 'No Course')); ?>
                        </p>

                        <div class="text-left mt-3">
                            <p><strong>Student ID:</strong>
                                <?php echo htmlspecialchars(getStudentData($student, 'id')); ?></p>
                            <p><strong>Username:</strong>
                                <?php echo htmlspecialchars(getStudentData($student, 'username')); ?></p>
                            <p><strong>Year & Section:</strong> Year
                                <?php echo htmlspecialchars(getStudentData($student, 'year', 'N/A')); ?> -
                                <?php echo htmlspecialchars(getStudentData($student, 'section', 'N/A')); ?>
                            </p>
                            <p><strong>Gender:</strong>
                                <?php echo htmlspecialchars(ucfirst(getStudentData($student, 'gender'))); ?></p>
                            <p><strong>Birthdate:</strong>
                                <?php echo getStudentData($student, 'birthdate') ? date('F j, Y', strtotime($student['birthdate'])) : 'Not specified'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Photo Update Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Update Profile Photo</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="photo">Choose Photo</label>
                                <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*">
                                <small class="form-text text-muted">Max file size: 2MB. Allowed types: JPG, JPEG, PNG,
                                    GIF</small>
                            </div>
                            <button type="submit" name="update_photo" class="btn btn-primary btn-block">Update
                                Photo</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Profile Form Column -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title">Edit Profile Information</h4>
                        <p class="card-category">Update your personal, academic, and guardian information</p>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <!-- Personal Information Section -->
                            <h5 class="section-title">Personal Information</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstname" class="bmd-label-floating">First Name *</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" value=""
                                            disabled>
                                        <?php echo htmlspecialchars(getStudentData($student, 'firstname')); ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="middlename" class="bmd-label-floating">Middle Name</label>
                                        <input type="text" class="form-control" id="middlename" name="middlename"
                                            value="" disabled>
                                        <?php echo htmlspecialchars(getStudentData($student, 'middlename')); ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="lastname" class="bmd-label-floating">Last Name *</label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" value=""
                                            disabled>
                                        <?php echo htmlspecialchars(getStudentData($student, 'lastname')); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="birthdate" class="bmd-label-floating">Birthdate</label>
                                        <input type="date" class="form-control" id="birthdate" name="birthdate"
                                            value="<?php echo htmlspecialchars(getStudentData($student, 'birthdate')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gender" class="bmd-label-floating">Gender *</label>
                                        <select class="form-control" id="gender" name="gender" required>
                                            <option value="male" <?php echo (getStudentData($student, 'gender') === 'male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo (getStudentData($student, 'gender') === 'female') ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nationality" class="bmd-label-floating">Nationality</label>
                                        <input type="text" class="form-control" id="nationality" name="nationality"
                                            value="<?php echo htmlspecialchars(getStudentData($student, 'nationality')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact" class="bmd-label-floating">Contact Number</label>
                                        <input type="text" class="form-control" id="contact" name="contact"
                                            value="<?php echo htmlspecialchars(getStudentData($student, 'contact')); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Academic Information Section -->
                            <h5 class="section-title mt-4">Academic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username" class="bmd-label-floating">Username *</label>
                                        <input type="text" class="form-control" id="username" name="username"
                                            value="<?php echo htmlspecialchars(getStudentData($student, 'username')); ?>"
                                            required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="course" class="bmd-label-floating">Course</label>
                                        <input type="text" class="form-control" id="course" name="course"
                                            value="<?php echo htmlspecialchars(getStudentData($student, 'course')); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="year" class="bmd-label-floating">Year</label>
                                        <select class="form-control" id="year" name="year">
                                            <option value="">Select Year</option>
                                            <option value="1" <?php echo (getStudentData($student, 'year') == 1) ? 'selected' : ''; ?>>1st Year</option>
                                            <option value="2" <?php echo (getStudentData($student, 'year') == 2) ? 'selected' : ''; ?>>2nd Year</option>
                                            <option value="3" <?php echo (getStudentData($student, 'year') == 3) ? 'selected' : ''; ?>>3rd Year</option>
                                            <option value="4" <?php echo (getStudentData($student, 'year') == 4) ? 'selected' : ''; ?>>4th Year</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="section" class="bmd-label-floating">Section</label>
                                        <input type="text" class="form-control" id="section" name="section"
                                            value="<?php echo htmlspecialchars(getStudentData($student, 'section')); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Guardian Information Section -->
                            <h5 class="section-title mt-4">Guardian Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="guardian_name" class="bmd-label-floating">Guardian Name</label>
                                        <input type="text" class="form-control" id="guardian_name" name="guardian_name"
                                            value="<?php echo htmlspecialchars(getStudentData($student, 'guardian_name')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="relationship" class="bmd-label-floating">Relationship</label>
                                        <input type="text" class="form-control" id="relationship" name="relationship"
                                            value="<?php echo htmlspecialchars(getStudentData($student, 'relationship')); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="guardian_contact" class="bmd-label-floating">Guardian
                                            Contact</label>
                                        <input type="text" class="form-control" id="guardian_contact"
                                            name="guardian_contact"
                                            value="<?php echo htmlspecialchars(getStudentData($student, 'guardian_contact')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address" class="bmd-label-floating">Address</label>
                                        <input type="text" class="form-control" id="address" name="address"
                                            value="<?php echo htmlspecialchars(getStudentData($student, 'address')); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Password Change Section -->
                            <h5 class="section-title mt-4">Change Password (Optional)</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="current_password" class="bmd-label-floating">Current
                                            Password</label>
                                        <input type="password" class="form-control" id="current_password"
                                            name="current_password">
                                        <small class="form-text text-muted">Leave blank if not changing password</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="new_password" class="bmd-label-floating">New Password</label>
                                        <input type="password" class="form-control" id="new_password"
                                            name="new_password">
                                        <small class="form-text text-muted">Min. 6 characters</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="confirm_password" class="bmd-label-floating">Confirm New
                                            Password</label>
                                        <input type="password" class="form-control" id="confirm_password"
                                            name="confirm_password">
                                    </div>
                                </div>
                            </div>

                            <!-- Single Update Button -->
                            <div class="text-center mt-4">
                                <button type="submit" name="update_profile" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .section-title {
        color: #9c27b0;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }
</style>

<?php
// Close connection
$pdo = null;
include('includes/footer.php');
?>