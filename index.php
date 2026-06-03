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
    <title>Login - Leave Management System</title>
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
            <p class="text-secondary">Welcome back! Please login to your account.</p>
        </div>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label fw-medium">Email address</label>
                <input type="email" class="form-control form-control-lg" id="email" name="email" required placeholder="name@company.com">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label fw-medium">Password</label>
                <input type="password" class="form-control form-control-lg" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">Sign In</button>
        </form>
        
        <div class="mt-4 text-center text-muted small">
            <p>Demo Admin: manager@test.com / password<br>Demo Employee: employee@test.com / password</p>
        </div>
         <a href="./register.php">Don't have any account .Register here</a> 
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
