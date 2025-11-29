<?php
//filename submit-form.php
session_start();
require '../includes/db_connect.php'; // Ensure the correct path for db.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form data (retrieving the POST values)
    $firstname = $_POST['firstname'] ?? '';
    $middlename = $_POST['middlename'] ?? '';  // Optional field
    $lastname = $_POST['lastname'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $guardian_name = $_POST['guardian_name'] ?? '';
    $relationship = $_POST['relationship'] ?? '';
    $address = $_POST['address'] ?? '';
    $guardian_contact = $_POST['guardian_contact'] ?? '';
    $course = $_POST['course'] ?? '';
    $section = $_POST['section'] ?? '';
    $year = $_POST['year'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';  // Optional field
    $password = $_POST['password'] ?? '';

    // Backend validation for required fields
    if (empty($firstname)) {
        die("First name is required!");
    }
    if (empty($lastname)) {
        die("Last name is required!");
    }
    if (empty($birthdate)) {
        die("Birthdate is required!");
    }
    if (empty($gender)) {
        die("Gender is required!");
    }
    if (empty($nationality)) {
        die("Nationality is required!");
    }
    if (empty($contact)) {
        die("Contact number is required!");
    }
    if (empty($guardian_name)) {
        die("Guardian name is required!");
    }
    if (empty($relationship)) {
        die("Relationship to student is required!");
    }
    if (empty($address)) {
        die("Address is required!");
    }
    if (empty($guardian_contact)) {
        die("Guardian contact is required!");
    }
    if (empty($course)) {
        die("Course is required!");
    }
    if (empty($section)) {
        die("Section is required!");
    }
    if (empty($year)) {
        die("Year is required!");
    }
    if (empty($username)) {
        die("Username is required!");
    }
    if (empty($password)) {
        die("Password is required!");
    }

    // File upload
    //$uploadDir = '../uploads/';  // Folder to save files
    //$fileName = time() . '-' . $_FILES['photo']['name'];  // Unique file name
    //$targetPath = $uploadDir . $fileName;

    // File upload handling - SIMPLIFIED VERSION
    $fileName = null; // Initialize as null in case no file is uploaded

    // Check if file was uploaded without errors

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
            $filename = 'student_' . '_' . time() . '.' . $file_extension;
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


            } else {
                $error = "Failed to upload photo. Please try again.";
            }
        }
    } else {
        $error = "Error uploading file. Please try again.";
    }

    // Password Hashing
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Hash password

    // Start a transaction to ensure both insertions are done atomically
    try {
        $pdo->beginTransaction();

        // Insert data into the students table
        $stmt = $pdo->prepare("
            INSERT INTO students (firstname, middlename, lastname, birthdate, gender, nationality, contact, 
                                  guardian_name, relationship, address, guardian_contact, course, section, year, username, password, photo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
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
            $hashedPassword,
            $fileName
        ]);

        // Insert data into the users table
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, email, role)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $username,
            $hashedPassword,
            $email,
            'student' // Default role is 'student'
        ]);

        // Commit the transaction
        $pdo->commit();

        // Redirect to success page
        header("Location: ../student");
        exit();

    } catch (Exception $e) {
        // Rollback in case of an error
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>