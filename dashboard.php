<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

// Get statistics
$stats = [];

// Total customers
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$stats['customers'] = $result->fetch_assoc()['count'];

// Total technicians
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'technician'");
$stats['technicians'] = $result->fetch_assoc()['count'];

// Today's jobs
$result = $conn->query("SELECT COUNT(*) as count FROM service_requests WHERE DATE(request_time) = CURDATE()");
$stats['today_jobs'] = $result->fetch_assoc()['count'];

// Pending jobs
$result = $conn->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'pending'");
$stats['pending_jobs'] = $result->fetch_assoc()['count'];

// Today's revenue
$result = $conn->query("SELECT COALESCE(SUM(price), 0) as total FROM service_requests WHERE status = 'completed' AND DATE(request_time) = CURDATE()");
$stats['today_revenue'] = $result->fetch_assoc()['total'];

// Recent jobs
$recentJobs = $conn->query("SELECT r.*, u.fullname as customer_name FROM service_requests r LEFT JOIN users u ON r.customer_id = u.user_id ORDER BY request_time DESC LIMIT 5");
?>

<div class="mb-4">
    <h3 class="fw-bold text-dark">ภาพรวมระบบ</h3>
    <p class="text-muted mb-0">ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card blue">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="small opacity-75">ลูกค้าทั้งหมด</div>
                    <div class="fs-3 fw-bold"><?php echo number_format($stats['customers']); ?></div>
                </div>
                <i class="fas fa-users fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card green">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="small opacity-75">ช่างทั้งหมด</div>
                    <div class="fs-3 fw-bold"><?php echo number_format($stats['technicians']); ?></div>
                </div>
                <i class="fas fa-wrench fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card orange">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="small opacity-75">งานวันนี้</div>
                    <div class="fs-3 fw-bold"><?php echo number_format($stats['today_jobs']); ?></div>
                </div>
                <i class="fas fa-clipboard-list fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card red">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="small opacity-75">รอรับงาน</div>
                    <div class="fs-3 fw-bold"><?php echo number_format($stats['pending_jobs']); ?></div>
                </div>
                <i class="fas fa-clock fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="card-custom p-4">
    <h5 class="fw-bold mb-3"><i class="fas fa-history text-primary me-2"></i>งานล่าสุด</h5>
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>รหัส</th>
                    <th>ลูกค้า</th>
                    <th>ปัญหา</th>
                    <th>สถานะ</th>
                    <th>เวลา</th>
                </tr>
            </thead>
            <tbody>
                <?php while($job = $recentJobs->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?php echo str_pad($job['request_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                    <td><?php echo htmlspecialchars($job['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($job['problem_type']); ?></td>
                    <td>
                        <?php
                        $statusColors = ['pending' => 'warning', 'accepted' => 'info', 'traveling' => 'info', 'working' => 'primary', 'completed' => 'success', 'cancelled' => 'danger'];
                        $statusTexts = ['pending' => 'รอรับงาน', 'accepted' => 'รับงานแล้ว', 'traveling' => 'กำลังเดินทาง', 'working' => 'กำลังซ่อม', 'completed' => 'เสร็จสิ้น', 'cancelled' => 'ยกเลิก'];
                        $color = $statusColors[$job['status']] ?? 'secondary';
                        $text = $statusTexts[$job['status']] ?? $job['status'];
                        ?>
                        <span class="badge bg-<?php echo $color; ?>"><?php echo $text; ?></span>
                    </td>
                    <td class="text-muted"><?php echo date('d/m/y H:i', strtotime($job['request_time'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
