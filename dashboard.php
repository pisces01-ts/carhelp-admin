<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

// Get statistics
$stats = [];

// Total customers
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$stats['customers'] = $result->fetch_assoc()['count'];

// Total technicians
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'technician' AND status = 'active'");
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

// Total revenue
$result = $conn->query("SELECT COALESCE(SUM(price), 0) as total FROM service_requests WHERE status = 'completed'");
$stats['total_revenue'] = $result->fetch_assoc()['total'];

// Pending technicians
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'technician' AND status = 'pending'");
$stats['pending_technicians'] = $result->fetch_assoc()['count'];

// Completed jobs
$result = $conn->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'completed'");
$stats['completed_jobs'] = $result->fetch_assoc()['count'];

// Recent jobs
$recentJobs = $conn->query("SELECT r.*, u.fullname as customer_name FROM service_requests r LEFT JOIN users u ON r.customer_id = u.user_id ORDER BY request_time DESC LIMIT 5");

// Recent reviews
$recentReviews = $conn->query("SELECT rv.*, u.fullname as customer_name, t.fullname as technician_name 
    FROM reviews rv 
    LEFT JOIN users u ON rv.customer_id = u.user_id 
    LEFT JOIN users t ON rv.technician_id = t.user_id 
    ORDER BY rv.created_at DESC LIMIT 5");

// Monthly stats for chart (last 6 months)
$monthlyStats = $conn->query("SELECT 
    DATE_FORMAT(request_time, '%Y-%m') as month,
    COUNT(*) as jobs,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END), 0) as revenue
    FROM service_requests 
    WHERE request_time >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(request_time, '%Y-%m')
    ORDER BY month");

$chartLabels = [];
$chartJobs = [];
$chartRevenue = [];
while ($row = $monthlyStats->fetch_assoc()) {
    $chartLabels[] = date('M Y', strtotime($row['month'] . '-01'));
    $chartJobs[] = intval($row['jobs']);
    $chartRevenue[] = floatval($row['revenue']);
}
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

<!-- Chart Section -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card-custom p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-chart-line text-primary me-2"></i>สถิติ 6 เดือนล่าสุด</h5>
            <canvas id="monthlyChart" height="120"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-4 h-100">
            <h5 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary me-2"></i>สรุป</h5>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">รายได้ทั้งหมด</span>
                    <strong class="text-success">฿<?php echo number_format($stats['total_revenue'], 0); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">งานเสร็จสิ้น</span>
                    <strong><?php echo number_format($stats['completed_jobs']); ?> งาน</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">รายได้วันนี้</span>
                    <strong class="text-primary">฿<?php echo number_format($stats['today_revenue'], 0); ?></strong>
                </div>
                <?php if ($stats['pending_technicians'] > 0): ?>
                <div class="alert alert-warning mt-3 mb-0 py-2">
                    <i class="fas fa-user-clock me-2"></i>มีช่างรออนุมัติ <?php echo $stats['pending_technicians']; ?> คน
                    <a href="pending_technicians.php" class="ms-2">ดู</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card-custom p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-history text-primary me-2"></i>งานล่าสุด</h5>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ลูกค้า</th>
                            <th>ปัญหา</th>
                            <th>สถานะ</th>
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
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <a href="manage_jobs.php" class="btn btn-outline-primary btn-sm mt-3">ดูทั้งหมด</a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-custom p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-star text-warning me-2"></i>รีวิวล่าสุด</h5>
            <?php if ($recentReviews && $recentReviews->num_rows > 0): ?>
            <div class="review-list">
                <?php while($review = $recentReviews->fetch_assoc()): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong><?php echo htmlspecialchars($review['customer_name'] ?? 'ลูกค้า'); ?></strong>
                        <span class="text-warning">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o text-muted'; ?>"></i>
                            <?php endfor; ?>
                        </span>
                    </div>
                    <small class="text-muted">ช่าง: <?php echo htmlspecialchars($review['technician_name'] ?? '-'); ?></small>
                    <?php if (!empty($review['comment'])): ?>
                    <p class="mb-0 mt-1 small"><?php echo htmlspecialchars(mb_substr($review['comment'], 0, 80)); ?>...</p>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <p class="text-muted text-center py-4">ยังไม่มีรีวิว</p>
            <?php endif; ?>
            <a href="reviews.php" class="btn btn-outline-primary btn-sm mt-2">ดูทั้งหมด</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [{
            label: 'จำนวนงาน',
            data: <?php echo json_encode($chartJobs); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            yAxisID: 'y'
        }, {
            label: 'รายได้ (บาท)',
            data: <?php echo json_encode($chartRevenue); ?>,
            type: 'line',
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.3,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
            y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'จำนวนงาน' } },
            y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'รายได้ (บาท)' }, grid: { drawOnChartArea: false } }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
