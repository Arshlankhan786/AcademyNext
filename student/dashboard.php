<?php
include 'includes/header.php';

// Get student payment summary
$payment_summary = $conn->query("
    SELECT 
        s.total_fees,
        COALESCE(SUM(p.amount_paid), 0) as total_paid,
        (s.total_fees - COALESCE(SUM(p.amount_paid), 0)) as pending_fees
    FROM students s
    LEFT JOIN payments p ON s.id = p.student_id
    WHERE s.id = {$student['id']}
    GROUP BY s.id
")->fetch_assoc();

$total_fees = $payment_summary['total_fees'];
$total_paid = $payment_summary['total_paid'];
$pending_fees = $payment_summary['pending_fees'];
$payment_percentage = ($total_fees > 0) ? ($total_paid / $total_fees) * 100 : 0;

// Get recent payments
$recent_payments = $conn->query("
    SELECT * FROM payments 
    WHERE student_id = {$student['id']} 
    ORDER BY payment_date DESC, created_at DESC 
    LIMIT 5
");

// Get course details
$course_details = $conn->query("
    SELECT s.*, c.name as course_name, cat.name as category_name
    FROM students s
    JOIN courses c ON s.course_id = c.id
    JOIN categories cat ON s.category_id = cat.id
    WHERE s.id = {$student['id']}
")->fetch_assoc();

// Calculate course progress
$enrollment_date = new DateTime($course_details['enrollment_date']);
$current_date = new DateTime();
$expected_end = clone $enrollment_date;
$expected_end->modify('+' . $course_details['duration_months'] . ' months');

$total_days = $enrollment_date->diff($expected_end)->days;
$days_elapsed = $enrollment_date->diff($current_date)->days;
$course_progress = min(100, ($days_elapsed / $total_days) * 100);

// Get recent notifications
$recent_notifications = $conn->query("
    SELECT * FROM student_notifications 
    WHERE student_id = {$student['id']} 
    ORDER BY created_at DESC 
    LIMIT 3
");
?>

<div class="page-header">
    <h2><i class="fas fa-tachometer-alt text-purple"></i> Dashboard</h2>
    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($student['name']); ?>!</p>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-lg-3 col-md-6">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Fees</p>
                        <h4 class="mb-0">₹<?php echo number_format($total_fees, 2); ?></h4>
                    </div>
                    <div class="card-icon icon-purple">
                    <i class="fa-solid fa-indian-rupee-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Amount Paid</p>
                        <h4 class="mb-0 text-success">₹<?php echo number_format($total_paid, 2); ?></h4>
                    </div>
                    <div class="card-icon icon-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Pending Fees</p>
                        <h4 class="mb-0 text-danger">₹<?php echo number_format($pending_fees, 2); ?></h4>
                    </div>
                    <div class="card-icon icon-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Duration</p>
                        <h4 class="mb-0"><?php echo $course_details['duration_months']; ?>M</h4>
                    </div>
                    <div class="card-icon icon-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment & Course Progress -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="table-card">
            <h6 class="text-purple mb-2"><i class="fas fa-chart-line"></i> Payment Progress</h6>
            <div class="progress progress-custom">
                <div class="progress-bar progress-bar-custom bg-success" 
                     style="width: <?php echo $payment_percentage; ?>%">
                    <?php echo number_format($payment_percentage, 1); ?>%
                </div>
            </div>
            <div class="mt-2">
                <div class="d-flex justify-content-between mb-1 small">
                    <span>Paid:</span>
                    <strong class="text-success">₹<?php echo number_format($total_paid, 2); ?></strong>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Remaining:</span>
                    <strong class="text-danger">₹<?php echo number_format($pending_fees, 2); ?></strong>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="table-card">
            <h6 class="text-purple mb-2"><i class="fas fa-graduation-cap"></i> Course Progress</h6>
            <div class="progress progress-custom">
                <div class="progress-bar progress-bar-custom <?php echo $course_progress >= 90 ? 'bg-danger' : ($course_progress >= 75 ? 'bg-warning' : 'bg-primary'); ?>" 
                     style="width: <?php echo $course_progress; ?>%">
                    <?php echo number_format($course_progress, 1); ?>%
                </div>
            </div>
            <div class="mt-2">
                <div class="d-flex justify-content-between mb-1 small">
                    <span>Enrolled:</span>
                    <strong><?php echo date('d M Y', strtotime($course_details['enrollment_date'])); ?></strong>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Expected End:</span>
                    <strong><?php echo $expected_end->format('d M Y'); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Course Details & Recent Payments -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="table-card">
            <h6 class="text-purple mb-2"><i class="fas fa-book"></i> My Course</h6>
            <table class="table table-sm mb-0">
                <tr>
                    <td class="small"><strong>Category:</strong></td>
                    <td class="small"><?php echo htmlspecialchars($course_details['category_name']); ?></td>
                </tr>
                <tr>
                    <td class="small"><strong>Course:</strong></td>
                    <td class="small"><?php echo htmlspecialchars($course_details['course_name']); ?></td>
                </tr>
                <tr>
                    <td class="small"><strong>Duration:</strong></td>
                    <td class="small"><?php echo $course_details['duration_months']; ?> Months</td>
                </tr>
                <tr>
                    <td class="small"><strong>Status:</strong></td>
                    <td><span class="badge bg-success small"><?php echo $course_details['status']; ?></span></td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="table-card">
            <h6 class="text-purple mb-2"><i class="fas fa-history"></i> Recent Payments</h6>
            <?php if ($recent_payments->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="small">Date</th>
                            <th class="small">Amount</th>
                            <th class="small">Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                        <tr>
                            <td class="small"><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                            <td><strong class="small">₹<?php echo number_format($payment['amount_paid'], 2); ?></strong></td>
                            <td><span class="badge bg-info small"><?php echo $payment['payment_method']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <a href="payments.php" class="btn btn-sm btn-outline-primary w-100 mt-2">
                View All <i class="fas fa-arrow-right"></i>
            </a>
            <?php else: ?>
            <p class="text-muted small mb-0">No payments yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Notifications -->
<?php if ($recent_notifications->num_rows > 0): ?>
<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="table-card">
            <h6 class="text-purple mb-2"><i class="fas fa-bell"></i> Recent Notifications</h6>
            <?php while ($notif = $recent_notifications->fetch_assoc()): ?>
            <div class="alert alert-<?php echo $notif['type'] === 'warning' ? 'warning' : 'info'; ?> py-2 px-3 small">
                <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                <small class="text-muted"><i class="fas fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?></small>
            </div>
            <?php endwhile; ?>
            <a href="notifications.php" class="btn btn-sm btn-outline-primary">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>