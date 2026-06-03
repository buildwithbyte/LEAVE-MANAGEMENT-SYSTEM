<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$first_day_ts = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day_ts);
$start_day_of_week = date('w', $first_day_ts); // 0 (Sun) to 6 (Sat)
$month_name = date('F', $first_day_ts);

// Fetch approved leaves for this month
$leaves = [];
$start_date_month = "$year-$month-01";
$end_date_month = "$year-$month-$days_in_month";

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'employee';

$where_clause = "lr.status = 'approved'";
if ($user_role !== 'manager') { // Only managers can see everyone's leave
    $where_clause .= " AND lr.user_id = $user_id";
}

$sql = "SELECT lr.*, u.name as employee_name 
        FROM leave_requests lr 
        JOIN users u ON lr.user_id = u.id 
        WHERE $where_clause 
        AND (
            (lr.start_date BETWEEN '$start_date_month' AND '$end_date_month') OR 
            (lr.end_date BETWEEN '$start_date_month' AND '$end_date_month') OR
            (lr.start_date <= '$start_date_month' AND lr.end_date >= '$end_date_month')
        )";

$res = $conn->query($sql);
while($row = $res->fetch_assoc()) {
    $leaves[] = $row;
}

// Function to check if a specific day has leaves
function getLeavesForDay($day, $month, $year, $leaves) {
    $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $found = [];
    foreach ($leaves as $l) {
        if ($current_date >= $l['start_date'] && $current_date <= $l['end_date']) {
            $found[] = $l;
        }
    }
    return $found;
}

// Prev/Next Links
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month == 0) { $prev_month = 12; $prev_year--; }

$next_month = $month + 1;
$next_year = $year;
if ($next_month == 13) { $next_month = 1; $next_year++; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Calendar - LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">LMS</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="calendar.php">Leave Calendar</a></li>
                <?php if ($_SESSION['user_role'] == 'manager'): ?>
                <li class="nav-item"><a class="nav-link" href="manage_employees.php">Manage Employees</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="glass-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0"><?= $month_name ?> <?= $year ?></h4>
            <div class="btn-group">
                <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-chevron-left"></i> Previous</a>
                <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn btn-outline-primary btn-sm">Next <i class="bi bi-chevron-right"></i></a>
            </div>
        </div>

        <div class="calendar-grid">
            <?php 
                $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach($days as $day): 
            ?>
                <div class="calendar-header"><?= $day ?></div>
            <?php endforeach; ?>

            <?php 
                // Empty slots before first day
                for($i = 0; $i < $start_day_of_week; $i++): 
            ?>
                <div class="calendar-day empty"></div>
            <?php endfor; ?>

            <?php 
                // Days of the month
                for($day = 1; $day <= $days_in_month; $day++): 
                    $day_leaves = getLeavesForDay($day, $month, $year, $leaves);
            ?>
                <div class="calendar-day">
                    <span class="calendar-date"><?= $day ?></span>
                    <?php foreach($day_leaves as $dl): ?>
                        <div class="leave-event" title="<?= htmlspecialchars($dl['employee_name']) ?>: <?= htmlspecialchars($dl['reason']) ?>">
                            <?= htmlspecialchars($dl['employee_name']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endfor; ?>

            <?php 
                // Empty slots after last day to complete the row
                $remaining = (7 - (($start_day_of_week + $days_in_month) % 7)) % 7;
                for($i = 0; $i < $remaining; $i++): 
            ?>
                <div class="calendar-day empty"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
