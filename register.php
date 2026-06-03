<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Leave Management System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="login-container">
    <div class="glass-card login-card">
        <div class="text-center mb-4">
            <h2 class="fw-bold" style="color: var(--primary-color);">LMS</h2>
            <p class="text-secondary">Create your account to get started.</p>
        </div>
         <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?> 
         <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success py-2" role="alert">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?> 

        <form action="register_handler.php" method="POST">
            <div class="mb-3">
                <label for="name" class="form-label fw-medium">Your Name</label>
                <input type="text" class="form-control form-control-lg" id="name" name="name" required placeholder="Enter your full name">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label fw-medium">Email address</label>
                <input type="email" class="form-control form-control-lg" id="email" name="email" required placeholder="name@company.com">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label fw-medium">Password</label>
                <input type="password" class="form-control form-control-lg" id="password" name="password" required placeholder="••••••••">
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label fw-medium">Confirm Password</label>
                <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">Register Now</button>
        </form>
        
        <div class="mt-4 text-center">
            <p class="text-muted small">Already have an account? <a href="index.php" class="fw-bold text-decoration-none">Login here</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

