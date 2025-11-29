<?php
session_start();

// Check if registration was successful
if (!isset($_SESSION['registration_success']) || !$_SESSION['registration_success']) {
    header("Location: student_registration.php");
    exit();
}

// Get student data from session
$student_data = $_SESSION['student_data'] ?? null;

// Clear the session data after displaying
unset($_SESSION['registration_success']);
unset($_SESSION['student_data']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Student Affairs System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            margin: 20px;
        }

        .success-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: bounce 1s infinite alternate;
        }

        @keyframes bounce {
            from {
                transform: translateY(0px);
            }

            to {
                transform: translateY(-10px);
            }
        }

        .student-info {
            padding: 30px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .action-buttons {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }

        .btn-outline-success {
            border-color: #28a745;
            color: #28a745;
            padding: 12px 30px;
            font-weight: 600;
        }

        .countdown {
            font-size: 0.9rem;
            color: #6c757d;
            text-align: center;
            margin-top: 15px;
        }

        .student-id-badge {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
        }
    </style>
</head>

<body>
    <div class="success-card">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="mb-3">Registration Successful!</h2>
            <p class="mb-0">Welcome to Student Affairs System</p>
        </div>

        <div class="student-info">
            <h4 class="text-center mb-4">Student Information</h4>

            <?php if ($student_data): ?>
                <div class="info-item">
                    <span class="info-label">Student ID:</span>
                    <span class="student-id-badge"><?php echo htmlspecialchars($student_data['id']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Full Name:</span>
                    <span
                        class="info-value"><?php echo htmlspecialchars($student_data['firstname'] . ' ' . $student_data['lastname']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student_data['username']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Course:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student_data['course']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Year & Section:</span>
                    <span class="info-value">Year <?php echo htmlspecialchars($student_data['year']); ?> -
                        <?php echo htmlspecialchars($student_data['section']); ?></span>
                </div>

                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle"></i>
                    <strong>Important:</strong> Please save your Student ID and remember your login credentials. You'll need
                    them to access the system.
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle"></i>
                    Student information not available.
                </div>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <div class="row g-3">
                <div class="col-md-6">
                    <a href="student_login.php" class="btn btn-success w-100">
                        <i class="fas fa-sign-in-alt"></i> Login Now
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="../index.php" class="btn btn-outline-success w-100">
                        <i class="fas fa-home"></i> Go to Homepage
                    </a>
                </div>
            </div>

            <div class="countdown">
                <i class="fas fa-clock"></i>
                Redirecting to login in <span id="countdown">10</span> seconds...
            </div>
        </div>
    </div>

    <script>
        // Auto-redirect countdown
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;

            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = 'student_login.php';
            }
        }, 1000);

        // Stop countdown if user interacts with the page
        document.addEventListener('click', function () {
            clearInterval(countdownInterval);
            countdownElement.textContent = '0';
            countdownElement.parentElement.innerHTML = '<i class="fas fa-info-circle"></i> Redirect cancelled due to user interaction';
        });
    </script>
</body>

</html>