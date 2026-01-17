<?php
include 'includes/header.php';

// ============================================
// REPORTS & ANALYTICS - NO CATEGORY DEPENDENCY
// ============================================

// Total Courses
$result = $conn->query("SELECT COUNT(*) as total FROM courses WHERE status = 'Active'");
$stats['total_courses'] = $result->fetch_assoc()['total'];

// Date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Overdue Students (no payment this month AND has pending)
$result = $conn->query("
    SELECT COUNT(DISTINCT s.id) as count
    FROM students s
    LEFT JOIN (
        SELECT student_id, SUM(amount_paid) as paid 
        FROM payments 
        GROUP BY student_id
    ) p ON s.id = p.student_id
    WHERE s.status = 'Active'
    AND (s.total_fees - COALESCE(p.paid, 0)) > 0
    AND NOT EXISTS (
        SELECT 1 FROM payments p2
        WHERE p2.student_id = s.id
        AND YEAR(p2.payment_date) = YEAR(CURDATE())
        AND MONTH(p2.payment_date) = MONTH(CURDATE())
    )
");
$stats['overdue_students'] = $result->fetch_assoc()['count'];

// ============================================
// OVERDUE STUDENTS (Top 5)
// ============================================
$overdueStudents = $conn->query("
    SELECT 
        s.id,
        s.student_code,
        s.full_name,
        s.phone,
        s.total_fees,
        COALESCE(SUM(p.amount_paid), 0) as total_paid,
        (s.total_fees - COALESCE(SUM(p.amount_paid), 0)) as pending_fees
    FROM students s
    LEFT JOIN payments p ON s.id = p.student_id
    WHERE s.status = 'Active'
    GROUP BY s.id
    HAVING pending_fees > 0
    AND NOT EXISTS (
        SELECT 1 FROM payments p2
        WHERE p2.student_id = s.id
        AND YEAR(p2.payment_date) = YEAR(CURDATE())
        AND MONTH(p2.payment_date) = MONTH(CURDATE())
    )
    ORDER BY pending_fees DESC
    LIMIT 5
");

// ============================================
// ACTIVE PAYING STUDENTS (for selected date range) - WITH DATES
// ============================================
$activePayingStudents = $conn->query("
    SELECT 
        s.id,
        s.student_code,
        s.full_name,
        COALESCE(SUM(p.amount_paid), 0) as month_paid,
        MIN(p.payment_date) as first_payment_date,
        MAX(p.payment_date) as last_payment_date,
        COUNT(p.id) as payment_count
    FROM students s
    JOIN payments p ON s.id = p.student_id 
        AND p.payment_date BETWEEN '$start_date' AND '$end_date'
    WHERE s.status = 'Active'
    GROUP BY s.id
    ORDER BY month_paid DESC
    LIMIT 10
");

// Revenue by course
$course_revenue = $conn->query("SELECT c.name, 
                                SUM(p.amount_paid) as total_revenue,
                                COUNT(DISTINCT s.id) as student_count
                                FROM payments p
                                JOIN students s ON p.student_id = s.id
                                JOIN courses c ON s.course_id = c.id
                                WHERE p.payment_date BETWEEN '$start_date' AND '$end_date'
                                GROUP BY c.id
                                ORDER BY total_revenue DESC
                                LIMIT 10");

// Monthly collection trend (last 12 months)
$monthly_collection = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $result = $conn->query("SELECT COALESCE(SUM(amount_paid), 0) as total 
                           FROM payments 
                           WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$month'");
    $monthly_collection[] = [
        'month' => date('M Y', strtotime($month . '-01')),
        'amount' => $result->fetch_assoc()['total']
    ];
}

// Payment method distribution
$payment_methods = $conn->query("SELECT payment_method, 
                                COUNT(*) as count,
                                SUM(amount_paid) as total
                                FROM payments
                                WHERE payment_date BETWEEN '$start_date' AND '$end_date'
                                GROUP BY payment_method");

// Top paying students
$top_students = $conn->query("SELECT s.student_code, s.full_name, 
                             SUM(p.amount_paid) as total_paid
                             FROM payments p
                             JOIN students s ON p.student_id = s.id
                             WHERE p.payment_date BETWEEN '$start_date' AND '$end_date'
                             GROUP BY s.id
                             ORDER BY total_paid DESC
                             LIMIT 10");

// Summary statistics
$total_collection = $conn->query("SELECT COALESCE(SUM(amount_paid), 0) as total 
                                 FROM payments 
                                 WHERE payment_date BETWEEN '$start_date' AND '$end_date'")->fetch_assoc()['total'];

$total_students = $conn->query("SELECT COUNT(DISTINCT student_id) as count 
                               FROM payments 
                               WHERE payment_date BETWEEN '$start_date' AND '$end_date'")->fetch_assoc()['count'];

// Duration-based revenue
$duration_revenue = $conn->query("SELECT 
                                  s.duration_months,
                                  SUM(p.amount_paid) as total_revenue,
                                  COUNT(DISTINCT s.id) as student_count
                                  FROM payments p
                                  JOIN students s ON p.student_id = s.id
                                  WHERE p.payment_date BETWEEN '$start_date' AND '$end_date'
                                  GROUP BY s.duration_months
                                  ORDER BY s.duration_months ASC");
?>

<div class="row">
    <h2 class="col-md-6"><i class="fas fa-tachometer-alt text-purple pb-4"></i> Current Month Fees Report</h2>

    <!-- Date Filter -->
    <div class="mb-4 col-md-6">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <!-- <small><label class="form-label">Start Date</label></small> -->
                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-5">
                <!-- <small><label class="form-label">End Date</label></small> -->
                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-purple w-100">
                    <i class="fas fa-filter"></i> 
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <!-- Active Paying Students (WITH DATES) -->
    <div class="col-lg-6 pt-2">
        <div class="table-card position-relative">
            <div style="top: -12px;right: 10px" class="card-icon icon-success position-absolute">
                <i class="fas fa-check-circle"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <p style="font-size: 12px;" class="p-0 m-0"><?php echo $total_students; ?></p>
                </span>
            </div>
            <h5 class="text-success mb-3"><i class="fas fa-check-circle"></i> Fees Paid</h5>
            <small class="text-muted d-block mb-2">Students who paid during this period</small>
            <?php if ($activePayingStudents->num_rows > 0): ?>  
            <div class="list-group list-group-flush">
                <?php while ($student = $activePayingStudents->fetch_assoc()): ?>
                <a href="student_details.php?id=<?php echo $student['id']; ?>" class="list-group-item list-group-item-action list-group-item-success d-flex justify-content-between">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                               
                            </div>
                            <!-- <small class="text-muted d-block"><?php // echo $student['student_code']; ?></small> -->
                           
                        </div>
                    </div>
                     <div class="mt-1">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php if ($student['first_payment_date'] == $student['last_payment_date']): ?>
                                        <?php echo date('d M Y', strtotime($student['first_payment_date'])); ?>
                                    <?php else: ?>
                                        <?php echo date('d M', strtotime($student['first_payment_date'])); ?> - <?php echo date('d M Y', strtotime($student['last_payment_date'])); ?>
                                    <?php endif; ?>
                                    <span class="badge bg-secondary ms-1"><?php echo $student['payment_count']; ?>x</span>
                                </small>
                            </div>
                             <span class="badge bg-success m-0 ">₹<?php echo number_format($student['month_paid'], 2); ?></span>
                </a>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No payments received during this period.
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Overdue Students -->
    <div class="col-lg-6 pt-2">
        <div class="table-card position-relative">
            <div style="top: -12px;right: 10px" class="card-icon icon-danger position-absolute">
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <p style="font-size: 12px;" class="p-0 m-0"><?php echo number_format($stats['overdue_students']); ?></p>
                </span>
                <i class="fas fa-clock"></i>
            </div>
            <h5 class="text-danger mb-3"><i class="fas fa-exclamation-triangle"></i> Overdue Students</h5>
            <small class="text-muted d-block mb-2">Students with no payment this month</small>
            <?php if ($overdueStudents->num_rows > 0): ?>
            <div class="list-group list-group-flush">
                <?php while ($student = $overdueStudents->fetch_assoc()): ?>
                <a href="student_details.php?id=<?php echo $student['id']; ?>" class="list-group-item list-group-item-action list-group-item-danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo $student['student_code']; ?></small>
                        </div>
                        <span class="badge bg-danger">₹<?php echo number_format($student['pending_fees'], 2); ?></span>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> All students are up to date!
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="col-md-6">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1"><?php echo date('M Y', strtotime($start_date)); ?> Total Collection</p>
                        <h3 class="mb-0 text-purple">₹<?php echo number_format($total_collection, 2); ?></h3>
                        <small class="text-muted"><?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></small>
                    </div>
                    <div class="card-icon icon-purple">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Courses Table -->
    <div class="col-lg-6">
        <div class="table-card">
            <h5 class="text-purple mb-3"><i class="fas fa-trophy"></i> Top Revenue Courses</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Students</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $course_revenue->data_seek(0);
                        while ($course = $course_revenue->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['name']); ?></td>
                            <td><span class="badge bg-info"><?php echo $course['student_count']; ?></span></td>
                            <td><strong>₹<?php echo number_format($course['total_revenue'], 2); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>

<script>
// ============================================
// CHART.JS v4 - ALL CHARTS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Course Revenue Chart (Doughnut)
    const courseCtx = document.getElementById('courseChart');
    if (courseCtx) {
        <?php 
        $course_revenue->data_seek(0);
        $course_names = [];
        $course_revenues = [];
        while ($c = $course_revenue->fetch_assoc()) {
            $course_names[] = $c['name'];
            $course_revenues[] = $c['total_revenue'];
        }
        ?>
        
        new Chart(courseCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($course_names); ?>,
                datasets: [{
                    data: <?php echo json_encode($course_revenues); ?>,
                    backgroundColor: [
                        '#7c3aed',
                        '#a78bfa',
                        '#c4b5fd',
                        '#ddd6fe',
                        '#ede9fe',
                        '#f5f3ff',
                        '#8b5cf6',
                        '#9333ea',
                        '#a855f7',
                        '#b794f4'
                    ]
                }]
            },
            options: { 
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ₹' + context.parsed.toLocaleString('en-IN');
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>