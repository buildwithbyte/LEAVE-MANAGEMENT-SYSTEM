<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Handle Leave Request Submission (Employee)
if (isset($_POST['submit_leave'])) {
    $leave_type = $conn->real_escape_string($_POST['leave_type']);
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = $conn->real_escape_string($_POST['end_date']);
    $reason = $conn->real_escape_string($_POST['reason']);

    // Basic date validation
    if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        header("Location: dashboard.php?error=Start date cannot be in the past");
        exit();
    }
    if (strtotime($start_date) > strtotime($end_date)) {
        header("Location: dashboard.php?error=End date cannot be before start date");
        exit();
    }

    $sql = "INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status) 
            VALUES ('$user_id', '$leave_type', '$start_date', '$end_date', '$reason', 'pending')";
    
    if ($conn->query($sql)) {
        header("Location: dashboard.php?msg=Leave request submitted successfully");
    } else {
        header("Location: dashboard.php?error=Error: " . $conn->error);
    }
    exit();
}

// Handle Leave Approval/Rejection (Manager)
if (isset($_POST['manage_leave']) && $user_role == 'manager') {
    $request_id = (int)$_POST['request_id'];
    $status = $conn->real_escape_string($_POST['status']); // 'approved' or 'rejected'
    $comment = $conn->real_escape_string($_POST['manager_comment']);

    // Get the request details first
    $res = $conn->query("SELECT * FROM leave_requests WHERE id = $request_id");
    $request = $res->fetch_assoc();

    if ($request) {
        $req_user_id = $request['user_id'];
        $leave_type = $request['leave_type'];
        
        // Calculate days
        $start = new DateTime($request['start_date']);
        $end = new DateTime($request['end_date']);
        $diff = $start->diff($end)->days + 1;

        $conn->begin_transaction();
        try {
            $conn->query("UPDATE leave_requests SET status = '$status', manager_comment = '$comment' WHERE id = $request_id");

            if ($status == 'approved') {
                // Update balance
                $conn->query("UPDATE leave_balances SET used_days = used_days + $diff 
                             WHERE user_id = $req_user_id AND leave_type = '$leave_type'");
            }
            
            $conn->commit();
            header("Location: dashboard.php?msg=Request $status successfully");
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: dashboard.php?error=Error processing request");
        }
    }
    exit();
}

// --- Employee CRUD (Manager Only) ---

// Add Employee
if (isset($_POST['add_employee']) && $user_role == 'manager') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email exists
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        header("Location: manage_employees.php?error=Email already registered");
        exit();
    }

    $conn->begin_transaction();
    try {
        $conn->query("INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')");
        $new_user_id = $conn->insert_id;

        if ($role == 'employee') {
            // Set default balances: 15 Vacation, 10 Sick
            $conn->query("INSERT INTO leave_balances (user_id, leave_type, total_days) VALUES ($new_user_id, 'vacation', 15)");
            $conn->query("INSERT INTO leave_balances (user_id, leave_type, total_days) VALUES ($new_user_id, 'sick', 10)");
        }

        $conn->commit();
        header("Location: manage_employees.php?msg=Employee added successfully");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: manage_employees.php?error=Error adding employee: " . $conn->error);
    }
    exit();
}

// Update Employee
if (isset($_POST['update_employee']) && $user_role == 'manager') {
    $target_id = (int)$_POST['target_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);

    $sql = "UPDATE users SET name='$name', email='$email', role='$role' WHERE id=$target_id";
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET name='$name', email='$email', role='$role', password='$password' WHERE id=$target_id";
    }

    if ($conn->query($sql)) {
        header("Location: manage_employees.php?msg=Employee updated successfully");
    } else {
        header("Location: manage_employees.php?error=Error updating employee");
    }
    exit();
}

// Delete Employee
if (isset($_POST['delete_employee']) && $user_role == 'manager') {
    $target_id = (int)$_POST['target_id'];
    
    // Prevent deleting self
    if ($target_id == $user_id) {
        header("Location: manage_employees.php?error=You cannot delete your own account");
        exit();
    }

    if ($conn->query("DELETE FROM users WHERE id = $target_id")) {
        header("Location: manage_employees.php?msg=Employee deleted successfully");
    } else {
        header("Location: manage_employees.php?error=Error deleting employee: " . $conn->error);
    }
    exit();
}

header("Location: dashboard.php");
exit();
?>
