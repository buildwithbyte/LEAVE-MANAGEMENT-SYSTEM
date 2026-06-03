<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'manager') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Fetch all users with leave stats
$users_res = $conn->query("SELECT u.*, 
                 SUM(CASE WHEN lb.leave_type = 'vacation' THEN lb.total_days ELSE 0 END) as vacation_total,
                 SUM(CASE WHEN lb.leave_type = 'vacation' THEN lb.used_days ELSE 0 END) as vacation_used,
                 SUM(CASE WHEN lb.leave_type = 'sick' THEN lb.total_days ELSE 0 END) as sick_total,
                 SUM(CASE WHEN lb.leave_type = 'sick' THEN lb.used_days ELSE 0 END) as sick_used,
                 (SELECT COUNT(*) FROM leave_requests WHERE user_id = u.id AND status = 'approved') as approved_count,
                 (SELECT COUNT(*) FROM leave_requests WHERE user_id = u.id AND status = 'rejected') as rejected_count
                 FROM users u
                 LEFT JOIN leave_balances lb ON u.id = lb.user_id
                 GROUP BY u.id
                 ORDER BY u.role DESC, u.name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">LMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="calendar.php">Leave Calendar</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_employees.php">Manage Employees</a></li>
            </ul>
            <div class="d-flex align-items-center">
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Employee Directory</h4>
        <button class="btn btn-primary fw-bold px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="bi bi-person-plus-fill me-2"></i>Add Employee
        </button>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="glass-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Vacation</th>
                        <th>Sick Leave</th>
                        <th>History</th>
                        <th>Role</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $modals_html = "";
                    while($row = $users_res->fetch_assoc()): 
                        // Edit Modal Buffer
                        $modals_html .= '
                        <div class="modal fade" id="editModal'.$row['id'].'" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 glass-card">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Edit Employee</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="actions.php" method="POST">
                                        <div class="modal-body pt-3">
                                            <input type="hidden" name="target_id" value="'.$row['id'].'">
                                            <div class="mb-3">
                                                <label class="form-label small fw-medium">Full Name</label>
                                                <input type="text" name="name" class="form-control" value="'.htmlspecialchars($row['name']).'" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-medium">Email Address</label>
                                                <input type="email" name="email" class="form-control" value="'.htmlspecialchars($row['email']).'" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-medium">Role</label>
                                                <select name="role" class="form-select" required>
                                                    <option value="employee" '.($row['role'] == 'employee' ? 'selected' : '').'>Employee</option>
                                                    <option value="manager" '.($row['role'] == 'manager' ? 'selected' : '').'>Manager</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-medium">New Password (leave blank to keep current)</label>
                                                <input type="password" name="password" class="form-control" placeholder="••••••••">
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="submit" name="update_employee" class="btn btn-primary w-100 fw-bold">Update Account</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>';

                        // Delete Modal Buffer
                        $modals_html .= '
                        <div class="modal fade" id="deleteModal'.$row['id'].'" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                <div class="modal-content border-0 glass-card text-center">
                                    <div class="modal-body p-4">
                                        <div class="text-danger mb-3" style="font-size: 3rem;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                                        <h5 class="fw-bold">Delete Employee?</h5>
                                        <p class="text-secondary small">This will permanently remove <strong>'.htmlspecialchars($row['name']).'</strong> and all their leave records.</p>
                                        <form action="actions.php" method="POST">
                                            <input type="hidden" name="target_id" value="'.$row['id'].'">
                                            <div class="d-grid gap-2">
                                                <button type="submit" name="delete_employee" class="btn btn-danger fw-bold">Delete Now</button>
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 1.2rem; flex-shrink: 0;">
                                    <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($row['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="min-width: 120px;">
                            <?php if($row['role'] == 'employee'): ?>
                                <div class="small mb-1">Used <?= $row['vacation_used'] ?>/<?= $row['vacation_total'] ?></div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-primary" style="width: <?= ($row['vacation_total'] > 0) ? ($row['vacation_used'] / $row['vacation_total'] * 100) : 0 ?>%"></div>
                                </div>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td style="min-width: 120px;">
                            <?php if($row['role'] == 'employee'): ?>
                                <div class="small mb-1">Used <?= $row['sick_used'] ?>/<?= $row['sick_total'] ?></div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-info" style="width: <?= ($row['sick_total'] > 0) ? ($row['sick_used'] / $row['sick_total'] * 100) : 0 ?>%"></div>
                                </div>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2" title="Approved">
                                    <i class="bi bi-check-circle-fill me-1"></i><?= $row['approved_count'] ?>
                                </span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2" title="Rejected">
                                    <i class="bi bi-x-circle-fill me-1"></i><?= $row['rejected_count'] ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?= $row['role'] == 'manager' ? 'info' : 'secondary' ?> rounded-pill px-3">
                                <?= ucfirst($row['role']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-light btn-sm rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>" title="Edit">
                                <i class="bi bi-pencil-fill text-primary"></i>
                            </button>
                            <?php if ($row['id'] != $user_id): ?>
                            <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>" title="Delete">
                                <i class="bi bi-trash-fill text-danger"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 glass-card">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions.php" method="POST">
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="john@company.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="employee">Employee</option>
                            <option value="manager">Manager</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="alert alert-info py-2 small" role="alert">
                        <i class="bi bi-info-circle me-2"></i>New employees will receive default leave balances (15 Vacation, 10 Sick).
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="add_employee" class="btn btn-primary w-100 fw-bold">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $modals_html ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
