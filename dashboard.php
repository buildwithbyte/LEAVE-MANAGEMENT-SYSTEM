<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Fetch leave balances for employees
$balances = [];
if ($user_role == 'employee') {
    $res = $conn->query("SELECT * FROM leave_balances WHERE user_id = $user_id");
    while ($row = $res->fetch_assoc()) {
        $balances[$row['leave_type']] = $row;
    }
}

// Fetch leave requests
if ($user_role == 'manager') {
    $requests_res = $conn->query("SELECT lr.*, u.name as employee_name 
                                 FROM leave_requests lr 
                                 JOIN users u ON lr.user_id = u.id 
                                 ORDER BY lr.created_at DESC");
} else {
    $requests_res = $conn->query("SELECT * FROM leave_requests WHERE user_id = $user_id ORDER BY created_at DESC");
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Leave Management System</title>
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
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="calendar.php">Leave Calendar</a>
                </li>
                <?php if ($user_role == 'manager'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="manage_employees.php">Manage Employees</a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <span class="me-3 text-secondary">Hello, <strong><?= htmlspecialchars($user_name) ?></strong> (<?= ucfirst($user_role) ?>)</span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container pb-5">
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

    <?php if ($user_role == 'employee'): ?>
        <!-- Employee View -->
        <div class="row g-4 mb-5">
            <?php foreach(['vacation', 'sick'] as $type): 
                $b = isset($balances[$type]) ? $balances[$type] : ['total_days' => 0, 'used_days' => 0];
                $remaining = $b['total_days'] - $b['used_days'];
            ?>
            <div class="col-md-6">
                <div class="glass-card p-4 stat-card h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-secondary mb-1"><?= ucfirst($type) ?> Leave</p>
                            <h2 class="fw-bold mb-0"><?= $remaining ?> <span class="fs-6 text-muted fw-normal">Days Left</span></h2>
                        </div>
                        <div class="stat-icon text-primary">
                            <i class="bi bi-<?= $type == 'vacation' ? 'sun' : 'thermometer-half' ?>"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar" style="width: <?= ($b['total_days'] > 0) ? ($b['used_days'] / $b['total_days'] * 100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="glass-card p-4">
                    <h5 class="fw-bold mb-4">Request Leave</h5>
                    <form action="actions.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Leave Type</label>
                            <select name="leave_type" class="form-select" required>
                                <option value="vacation">Vacation</option>
                                <option value="sick">Sick Leave</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Start Date</label>
                            <input type="date" name="start_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">End Date</label>
                            <input type="date" name="end_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Reason</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="submit_leave" class="btn btn-primary w-100 fw-bold">Submit Request</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="glass-card p-4">
                    <h5 class="fw-bold mb-4">Your Recent Requests</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Dates</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($requests_res->num_rows > 0): ?>
                                    <?php while($row = $requests_res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-capitalize"><?= $row['leave_type'] ?></td>
                                        <td class="small">
                                            <?= date('M d', strtotime($row['start_date'])) ?> - <?= date('M d, Y', strtotime($row['end_date'])) ?>
                                        </td>
                                        <td class="text-truncate" style="max-width: 150px;"><?= htmlspecialchars($row['reason']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $row['status'] ?> rounded-pill px-3"><?= ucfirst($row['status']) ?></span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-4">No requests found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Manager View -->
        <div class="glass-card p-4">
            <h4 class="fw-bold mb-4">Pending Leave Requests</h4>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Dates</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $modals_html = ""; 
                        if ($requests_res->num_rows > 0): 
                            while($row = $requests_res->fetch_assoc()): 
                                // Buffer modals to print them at the end of the body
                                if ($row['status'] == 'pending') {
                                    $modals_html .= '
                                    <div class="modal fade" id="manageModal'.$row['id'].'" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 glass-card">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold">Manage Request</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="actions.php" method="POST">
                                                    <div class="modal-body pt-3">
                                                        <input type="hidden" name="request_id" value="'.$row['id'].'">
                                                        <input type="hidden" name="manage_leave" value="1">
                                                        
                                                        <div class="leave-summary-card mb-4">
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <span class="text-secondary small">Employee</span>
                                                                <span class="fw-bold">'.htmlspecialchars($row['employee_name']).'</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <span class="text-secondary small">Leave Type</span>
                                                                <span class="badge bg-primary rounded-pill px-3">'.ucfirst($row['leave_type']).'</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <span class="text-secondary small">Duration</span>
                                                                <span class="fw-medium">'.date('M d', strtotime($row['start_date'])).' - '.date('M d, Y', strtotime($row['end_date'])).'</span>
                                                            </div>
                                                        </div>

                                                        <div class="mb-4">
                                                            <label class="form-label small fw-bold text-uppercase" style="letter-spacing: 0.5px; opacity: 0.7;">Manager Comment</label>
                                                            <textarea name="manager_comment" class="form-control" rows="3" placeholder="Add a note (optional)..."></textarea>
                                                        </div>

                                                        <label class="form-label small fw-bold text-uppercase mb-3" style="letter-spacing: 0.5px; opacity: 0.7;">Choose Action</label>
                                                        <div class="decision-btn-group">
                                                            <button type="submit" name="status" value="approved" class="btn-decision approve text-decoration-none border-0 w-100">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                                <span class="fw-bold">Approve</span>
                                                            </button>
                                                            <button type="submit" name="status" value="rejected" class="btn-decision reject text-decoration-none border-0 w-100">
                                                                <i class="bi bi-x-circle-fill"></i>
                                                                <span class="fw-bold">Reject</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>';
                                }
                        ?>
                            <tr>
                                <td class="fw-medium"><?= htmlspecialchars($row['employee_name']) ?></td>
                                <td class="text-capitalize"><?= $row['leave_type'] ?></td>
                                <td class="small">
                                    <?= date('M d', strtotime($row['start_date'])) ?> - <?= date('M d, Y', strtotime($row['end_date'])) ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="popover" title="Reason" data-bs-content="<?= htmlspecialchars($row['reason']) ?>">View</button>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $row['status'] ?> rounded-pill px-3"><?= ucfirst($row['status']) ?></span>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-success px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#manageModal<?= $row['id'] ?>">Manage</button>
                                    <?php else: ?>
                                        <small class="text-muted"><?= htmlspecialchars($row['manager_comment']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No pending requests found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?= $modals_html ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))

    // Dynamic date range restriction
    const startDateInput = document.querySelector('input[name="start_date"]');
    const endDateInput = document.querySelector('input[name="end_date"]');

    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            if (this.value) {
                endDateInput.setAttribute('min', this.value);
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
            }
        });
    }
</script>

</body>
</html>
