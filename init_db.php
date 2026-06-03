<?php
// Initialize Database System for Leave Management

$conn = new mysqli("localhost", "root", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Create DB if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS `leave-management`");
$conn->select_db("leave-management");

// 2. Create tables
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('manager', 'employee') NOT NULL DEFAULT 'employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql_users);

$sql_leave_balances = "CREATE TABLE IF NOT EXISTS leave_balances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    total_days INT NOT NULL,
    used_days INT NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql_leave_balances);

$sql_leave_requests = "CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    manager_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql_leave_requests);

// 3. Insert Default Users (Admin/Manager & Employee)
echo "Inserting default accounts...\\n";

// Check if manager exists
$result = $conn->query("SELECT * FROM users WHERE email='manager@test.com'");
if ($result->num_rows == 0) {
    $pw = password_hash('password', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (name, email, password, role) VALUES ('Admin Manager', 'manager@test.com', '$pw', 'manager')");
    echo "Manager created: manager@test.com / password \\n";
} else {
    echo "Manager already exists.\\n";
}

// Check if employee exists
$result = $conn->query("SELECT * FROM users WHERE email='employee@test.com'");
if ($result->num_rows == 0) {
    $pw = password_hash('password', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (name, email, password, role) VALUES ('John Doe', 'employee@test.com', '$pw', 'employee')");
    $emp_id = $conn->insert_id;
    // Insert balances
    $conn->query("INSERT INTO leave_balances (user_id, leave_type, total_days) VALUES ($emp_id, 'vacation', 15)");
    $conn->query("INSERT INTO leave_balances (user_id, leave_type, total_days) VALUES ($emp_id, 'sick', 10)");
    echo "Employee created: employee@test.com / password \\n";
} else {
    echo "Employee already exists.\\n";
}

echo "Database initialization complete.\\n";
?>
