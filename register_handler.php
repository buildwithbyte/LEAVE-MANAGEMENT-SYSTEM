<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: register.php?error=All fields are required");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }

    // Check if email already exists
    $check_email = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check_email && $check_email->num_rows > 0) {
        header("Location: register.php?error=Email already registered");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', 'employee')";
    
    if ($conn->query($sql)) {
        $user_id = $conn->insert_id;

        // Initialize default leave balances for the new employee
        // Based on init_db.php: vacation: 15, sick: 10
        $conn->query("INSERT INTO leave_balances (user_id, leave_type, total_days) VALUES ($user_id, 'vacation', 15)");
        $conn->query("INSERT INTO leave_balances (user_id, leave_type, total_days) VALUES ($user_id, 'sick', 10)");

        header("Location: register.php?success=Registration successful! You can now login.");
        exit();
    } else {
        header("Location: register.php?error=Registration failed. Please try again.");
        exit();
    }
}

header("Location: register.php");
exit();
